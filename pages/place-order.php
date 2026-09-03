<?php

session_start();
require_once "../db.php";

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];


/* =====================================================
   GET CHECKOUT FORM DATA
===================================================== */

$recipient_name = trim($_POST["recipient_name"] ?? "");
$phone          = trim($_POST["phone"] ?? "");
$address_line   = trim($_POST["address_line"] ?? "");
$barangay       = trim($_POST["barangay"] ?? "");
$city           = trim($_POST["city"] ?? "");
$province       = trim($_POST["province"] ?? "");
$postal_code    = trim($_POST["postal_code"] ?? "");
$payment_method = trim($_POST["payment_method"] ?? "");


/* =====================================================
   VALIDATE DELIVERY INFORMATION
===================================================== */

if (
    $recipient_name === "" ||
    $phone === "" ||
    $address_line === "" ||
    $barangay === "" ||
    $city === "" ||
    $province === ""
) {
    die("Please complete all required delivery information.");
}


/* =====================================================
   VALIDATE PAYMENT METHOD
===================================================== */

$allowed_payment_methods = [
    "cod",
    "gcash"
];

if (!in_array($payment_method, $allowed_payment_methods, true)) {
    die("Please select a valid payment method.");
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
    die("Your cart is empty.");
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
    die("Your cart is empty.");
}


/* =====================================================
   START TRANSACTION
===================================================== */

try {

    $pdo->beginTransaction();


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

        if ($product["status"] !== "active") {

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
       CREATE DELIVERY ADDRESS
    ================================================= */

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
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
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
            : null
    ]);

    $address_id = $pdo->lastInsertId();


    /* =================================================
       CREATE ORDER
    ================================================= */

    $orderStmt = $pdo->prepare("
        INSERT INTO orders
        (
            user_id,
            address_id,
            total_amount,
            status
        )
        VALUES (?, ?, ?, 'Pending')
    ");

    $orderStmt->execute([
        $user_id,
        $address_id,
        $total_amount
    ]);

    $order_id = $pdo->lastInsertId();


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


        $quantity = (int)$item["quantity"];

        $price = (float)$product["price"];

        $subtotal = $price * $quantity;


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


        if ($stockStmt->rowCount() !== 1) {

            throw new Exception(
                "Unable to update stock for " .
                $product["product_name"]
            );
        }
    }


    /* =================================================
       CREATE PAYMENT
    ================================================= */

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
        VALUES (?, ?, ?, 'Pending', NULL, NULL)
    ");

    $paymentStmt->execute([
        $order_id,
        $payment_method,
        $total_amount
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

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die(
        "Order could not be completed: " .
        htmlspecialchars($e->getMessage())
    );
}