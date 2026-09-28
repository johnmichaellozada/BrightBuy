<?php

session_start();
require_once "../db.php";


/* =====================================================
   CUSTOMER ACCESS CHECK
   CHECK ROLE DIRECTLY FROM DATABASE
===================================================== */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}


$userRoleStmt = $pdo->prepare("
    SELECT role
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$userRoleStmt->execute([
    $_SESSION["user_id"]
]);

$currentUser = $userRoleStmt->fetch();


if (
    !$currentUser ||
    $currentUser["role"] !== "customer"
) {
    header("Location: ../index.php");
    exit;
}


$user_id = $_SESSION["user_id"];


/* =====================================================
   GET CHECKOUT DATA
===================================================== */

$address_option =
    trim($_POST["address_option"] ?? "");


$address_id =
    filter_input(
        INPUT_POST,
        "address_id",
        FILTER_VALIDATE_INT
    );


$recipient_name =
    trim($_POST["recipient_name"] ?? "");


$phone =
    trim($_POST["phone"] ?? "");


$address_line =
    trim($_POST["address_line"] ?? "");


$barangay =
    trim($_POST["barangay"] ?? "");


$city =
    trim($_POST["city"] ?? "");


$province =
    trim($_POST["province"] ?? "");


$postal_code =
    trim($_POST["postal_code"] ?? "");


$payment_method =
    trim($_POST["payment_method"] ?? "");


$save_as_default =
    isset($_POST["save_as_default"]) &&
    $_POST["save_as_default"] === "1";


/* =====================================================
   GET GCASH INFORMATION
===================================================== */

$gcash_number =
    trim($_POST["gcash_number"] ?? "");


$gcash_reference =
    trim($_POST["gcash_reference"] ?? "");


/* =====================================================
   VALIDATE PAYMENT METHOD
===================================================== */

$allowed_payment_methods = [
    "cod",
    "gcash"
];


if (
    !in_array(
        $payment_method,
        $allowed_payment_methods,
        true
    )
) {

    die(
        "Please select a valid payment method."
    );
}


/* =====================================================
   VALIDATE GCASH INFORMATION
===================================================== */

if ($payment_method === "gcash") {


    /* ---------------------------------------------
       GCASH NUMBER REQUIRED
    --------------------------------------------- */

    if ($gcash_number === "") {

        die(
            "GCash number is required."
        );
    }


    /* ---------------------------------------------
       GCASH REFERENCE REQUIRED
    --------------------------------------------- */

    if ($gcash_reference === "") {

        die(
            "GCash reference number is required."
        );
    }


    /* ---------------------------------------------
       VALIDATE GCASH NUMBER
       PHILIPPINE MOBILE FORMAT
       09XXXXXXXXX
    --------------------------------------------- */

    if (
        !preg_match(
            '/^09[0-9]{9}$/',
            $gcash_number
        )
    ) {

        die(
            "Please enter a valid 11-digit GCash number."
        );
    }


    /* ---------------------------------------------
       VALIDATE REFERENCE LENGTH
    --------------------------------------------- */

    if (
        strlen($gcash_reference) > 100
    ) {

        die(
            "GCash reference number is too long."
        );
    }

} else {


    /*
       COD DOES NOT NEED GCASH INFORMATION.
       Store NULL instead.
    */

    $gcash_number = null;

    $gcash_reference = null;
}


/* =====================================================
   GET USER CART
===================================================== */

$cartStmt = $pdo->prepare("
    SELECT cart_id
    FROM cart
    WHERE user_id = ?
    LIMIT 1
");

$cartStmt->execute([
    $user_id
]);

$cart = $cartStmt->fetch();


if (!$cart) {

    die(
        "Your cart is empty."
    );
}


$cart_id = $cart["cart_id"];


/* =====================================================
   GET CART ITEMS
===================================================== */

$itemsStmt = $pdo->prepare("
    SELECT
        ci.cart_item_id,
        ci.product_id,
        ci.quantity,
        p.product_name,
        p.price,
        p.stock,
        p.status
    FROM cart_items ci
    INNER JOIN products p
        ON ci.product_id = p.product_id
    WHERE ci.cart_id = ?
");

$itemsStmt->execute([
    $cart_id
]);

$items = $itemsStmt->fetchAll();


if (!$items) {

    die(
        "Your cart is empty."
    );
}


/* =====================================================
   START TRANSACTION
===================================================== */

try {

    $pdo->beginTransaction();


    /* =================================================
       HANDLE DELIVERY ADDRESS
    ================================================= */


    /*
       OPTION 1:
       USE SAVED ADDRESS
    */

    if (
        $address_option === "saved" &&
        $address_id
    ) {


        $savedAddressStmt = $pdo->prepare("
            SELECT
                address_id,
                user_id,
                recipient_name,
                phone,
                address_line,
                barangay,
                city,
                province,
                postal_code
            FROM addresses
            WHERE address_id = ?
              AND user_id = ?
            LIMIT 1
        ");


        $savedAddressStmt->execute([
            $address_id,
            $user_id
        ]);


        $savedAddress =
            $savedAddressStmt->fetch();


        /*
           SECURITY CHECK:
           Make sure the address belongs
           to the currently logged-in user.
        */

        if (!$savedAddress) {

            throw new Exception(
                "The selected delivery address is invalid."
            );
        }


        /*
           Use the existing address.
           NO new address is created.
        */

        $address_id =
            (int)$savedAddress["address_id"];


    } else {


        /*
           OPTION 2:
           CREATE / REUSE NEW ADDRESS
        */

        if (
            $recipient_name === "" ||
            $phone === "" ||
            $address_line === "" ||
            $barangay === "" ||
            $city === "" ||
            $province === ""
        ) {

            throw new Exception(
                "Please complete all required delivery information."
            );
        }


        /* ---------------------------------------------
           CHECK IF EXACT SAME ADDRESS ALREADY EXISTS
        --------------------------------------------- */

        $duplicateAddressStmt = $pdo->prepare("
            SELECT
                address_id
            FROM addresses
            WHERE user_id = ?
              AND recipient_name = ?
              AND phone = ?
              AND address_line = ?
              AND barangay = ?
              AND city = ?
              AND province = ?
              AND (
                    postal_code = ?
                    OR (
                        postal_code IS NULL
                        AND ? = ''
                    )
                  )
            ORDER BY address_id DESC
            LIMIT 1
        ");


        $duplicateAddressStmt->execute([
            $user_id,
            $recipient_name,
            $phone,
            $address_line,
            $barangay,
            $city,
            $province,
            $postal_code !== ""
                ? $postal_code
                : null,
            $postal_code
        ]);


        $existingAddress =
            $duplicateAddressStmt->fetch();


        if ($existingAddress) {


            /*
               Exact same address already exists.
               Reuse it instead of creating another
               duplicate address.
            */

            $address_id =
                (int)$existingAddress["address_id"];


        } else {


            /*
               If customer wants this to be
               the default address, remove
               default status from other addresses.
            */

            if ($save_as_default) {

                $removeDefaultStmt = $pdo->prepare("
                    UPDATE addresses
                    SET is_default = 0
                    WHERE user_id = ?
                ");

                $removeDefaultStmt->execute([
                    $user_id
                ]);
            }


            /*
               Create new address.
            */

            $addressStmt = $pdo->prepare("
                INSERT INTO addresses
                (
                    user_id,
                    recipient_name,
                    phone,
                    address_line,
                    barangay,
                    city,
                    province,
                    postal_code,
                    is_default
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");


            $addressStmt->execute([
                $user_id,
                $recipient_name,
                $phone,
                $address_line,
                $barangay,
                $city,
                $province,
                $postal_code !== ""
                    ? $postal_code
                    : null,
                $save_as_default ? 1 : 0
            ]);


            $address_id =
                (int)$pdo->lastInsertId();


            /*
               If this is the customer's first
               address, automatically make it default.
            */

            $addressCountStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM addresses
                WHERE user_id = ?
            ");


            $addressCountStmt->execute([
                $user_id
            ]);


            $addressCount =
                (int)$addressCountStmt->fetchColumn();


            if ($addressCount === 1) {

                $makeDefaultStmt = $pdo->prepare("
                    UPDATE addresses
                    SET is_default = 1
                    WHERE address_id = ?
                      AND user_id = ?
                ");


                $makeDefaultStmt->execute([
                    $address_id,
                    $user_id
                ]);
            }
        }
    }


    /* =================================================
       CHECK STOCK
    ================================================= */

    $total_amount = 0;


    foreach ($items as $item) {


        $productStmt = $pdo->prepare("
            SELECT
                product_id,
                product_name,
                price,
                stock,
                status
            FROM products
            WHERE product_id = ?
            FOR UPDATE
        ");


        $productStmt->execute([
            $item["product_id"]
        ]);


        $product = $productStmt->fetch();


        if (!$product) {

            throw new Exception(
                "Product no longer exists."
            );
        }


        /* ---------------------------------------------
           CHECK PRODUCT STATUS
        --------------------------------------------- */

        if (
            $product["status"] !== "active"
        ) {

            throw new Exception(
                $product["product_name"] .
                " is currently unavailable."
            );
        }


        /* ---------------------------------------------
           CHECK STOCK
        --------------------------------------------- */

        if (
            (int)$product["stock"] <
            (int)$item["quantity"]
        ) {

            throw new Exception(
                "Not enough stock for " .
                $product["product_name"] .
                ". Available stock: " .
                $product["stock"]
            );
        }


        /* ---------------------------------------------
           CALCULATE TOTAL
        --------------------------------------------- */

        $total_amount +=
            (float)$product["price"] *
            (int)$item["quantity"];
    }


    /* =================================================
       CREATE ORDER
    ================================================= */

    $orderStmt = $pdo->prepare("
        INSERT INTO orders
        (
            user_id,
            address_id,
            total_amount,
            gcash_number,
            gcash_reference,
            status
        )
        VALUES (?, ?, ?, ?, ?, 'Pending')
    ");


    $orderStmt->execute([
        $user_id,
        $address_id,
        $total_amount,
        $gcash_number,
        $gcash_reference
    ]);


    $order_id =
        $pdo->lastInsertId();


    /* =================================================
       CREATE ORDER ITEMS
       + REDUCE STOCK
    ================================================= */

    foreach ($items as $item) {


        $productStmt = $pdo->prepare("
            SELECT
                product_id,
                product_name,
                price,
                stock
            FROM products
            WHERE product_id = ?
            FOR UPDATE
        ");


        $productStmt->execute([
            $item["product_id"]
        ]);


        $product = $productStmt->fetch();


        if (!$product) {

            throw new Exception(
                "Product not found."
            );
        }


        $quantity =
            (int)$item["quantity"];


        $price =
            (float)$product["price"];


        $subtotal =
            $price * $quantity;


        /* ---------------------------------------------
           CREATE ORDER ITEM
        --------------------------------------------- */

        $orderItemStmt = $pdo->prepare("
            INSERT INTO order_items
            (
                order_id,
                product_id,
                quantity,
                price,
                subtotal
            )
            VALUES (?, ?, ?, ?, ?)
        ");


        $orderItemStmt->execute([
            $order_id,
            $product["product_id"],
            $quantity,
            $price,
            $subtotal
        ]);


        /* ---------------------------------------------
           REDUCE STOCK
        --------------------------------------------- */

        $stockStmt = $pdo->prepare("
            UPDATE products
            SET stock = stock - ?
            WHERE product_id = ?
              AND stock >= ?
        ");


        $stockStmt->execute([
            $quantity,
            $product["product_id"],
            $quantity
        ]);


        if (
            $stockStmt->rowCount() !== 1
        ) {

            throw new Exception(
                "Unable to update stock for " .
                $product["product_name"]
            );
        }
    }


    /* =================================================
       CREATE PAYMENT
    ================================================= */

    /*
       For GCash:
       transaction_reference = GCash reference number

       For COD:
       transaction_reference = NULL
    */

    $paymentStmt = $pdo->prepare("
        INSERT INTO payments
        (
            order_id,
            payment_method,
            amount,
            payment_status,
            transaction_reference,
            paid_at
        )
        VALUES (?, ?, ?, 'Pending', ?, NULL)
    ");


    $paymentStmt->execute([
        $order_id,
        $payment_method,
        $total_amount,
        $gcash_reference
    ]);


    /* =================================================
       CLEAR CART
    ================================================= */

    $clearCartStmt = $pdo->prepare("
        DELETE FROM cart_items
        WHERE cart_id = ?
    ");


    $clearCartStmt->execute([
        $cart_id
    ]);


    /* =================================================
       COMMIT TRANSACTION
    ================================================= */

    $pdo->commit();


    /* =================================================
       GO TO SUCCESS PAGE
    ================================================= */

    header(
        "Location: order-success.php?order_id=" .
        $order_id
    );

    exit;


} catch (Exception $e) {


    /* =================================================
       ROLLBACK IF SOMETHING FAILS
    ================================================= */

    if ($pdo->inTransaction()) {

        $pdo->rollBack();
    }


    die(
        "Order could not be completed: " .
        htmlspecialchars(
            $e->getMessage()
        )
    );
}

