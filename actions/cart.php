<?php

/**
 * BrightBuy Cart Action Handler
 *
 * Handles:
 * 1. Add product to cart
 * 2. Update cart quantity
 * 3. Remove cart item
 * 4. Clear cart
 */

session_start();

require_once "../db.php";


/* =========================================================
   CUSTOMER LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: ../pages/login.php");
    exit;
}


/* =========================================================
   GET CUSTOMER ID
========================================================= */

$user_id = (int) ($_SESSION["user_id"] ?? 0);

if ($user_id <= 0) {
    header("Location: ../pages/login.php");
    exit;
}


/* =========================================================
   ONLY ALLOW POST REQUESTS
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/cart.php");
    exit;
}


/* =========================================================
   GET ACTION
========================================================= */

$action = $_POST["action"] ?? "";


/* =========================================================
   GET CUSTOMER CART
========================================================= */

$cartStmt = $pdo->prepare("
    SELECT cart_id
    FROM cart
    WHERE user_id = ?
    LIMIT 1
");

$cartStmt->execute([$user_id]);

$cart = $cartStmt->fetch();


/* =========================================================
   CREATE CART IF CUSTOMER DOES NOT HAVE ONE
========================================================= */

if (!$cart) {

    $createCart = $pdo->prepare("
        INSERT INTO cart (user_id)
        VALUES (?)
    ");

    $createCart->execute([$user_id]);

    $cart_id = (int) $pdo->lastInsertId();

} else {

    $cart_id = (int) $cart["cart_id"];
}


/* =========================================================
   ADD PRODUCT TO CART
========================================================= */

if ($action === "add") {

    $product_id = filter_input(
        INPUT_POST,
        "product_id",
        FILTER_VALIDATE_INT
    );

    $quantity = filter_input(
        INPUT_POST,
        "quantity",
        FILTER_VALIDATE_INT
    );


    /* -----------------------------------------------------
       VALIDATE PRODUCT
    ----------------------------------------------------- */

    if (!$product_id || $product_id <= 0) {

        header(
            "Location: ../pages/shop.php?error=invalid_product"
        );

        exit;
    }


    /* -----------------------------------------------------
       DEFAULT QUANTITY
    ----------------------------------------------------- */

    if (!$quantity || $quantity < 1) {
        $quantity = 1;
    }


    /* -----------------------------------------------------
       GET PRODUCT STOCK
    ----------------------------------------------------- */

    $productStmt = $pdo->prepare("
        SELECT
            product_id,
            product_name,
            stock
        FROM products
        WHERE product_id = ?
        LIMIT 1
    ");

    $productStmt->execute([$product_id]);

    $product = $productStmt->fetch();


    /* -----------------------------------------------------
       PRODUCT NOT FOUND
    ----------------------------------------------------- */

    if (!$product) {

        header(
            "Location: ../pages/shop.php?error=product_not_found"
        );

        exit;
    }


    $stock = (int) $product["stock"];


    /* -----------------------------------------------------
       OUT OF STOCK
    ----------------------------------------------------- */

    if ($stock <= 0) {

        header(
            "Location: ../pages/shop.php?error=out_of_stock"
        );

        exit;
    }


    /* =====================================================
       CHECK IF PRODUCT ALREADY EXISTS IN CART
    ===================================================== */

    $checkStmt = $pdo->prepare("
        SELECT
            cart_item_id,
            quantity
        FROM cart_items
        WHERE cart_id = ?
          AND product_id = ?
        LIMIT 1
    ");

    $checkStmt->execute([
        $cart_id,
        $product_id
    ]);

    $existingItem = $checkStmt->fetch();


    /* =====================================================
       PRODUCT ALREADY IN CART
    ===================================================== */

    if ($existingItem) {

        $currentQuantity =
            (int) $existingItem["quantity"];

        $newQuantity =
            $currentQuantity + $quantity;


        /* Never exceed stock */

        if ($newQuantity > $stock) {
            $newQuantity = $stock;
        }


        $updateStmt = $pdo->prepare("
            UPDATE cart_items
            SET quantity = ?
            WHERE cart_item_id = ?
              AND cart_id = ?
        ");

        $updateStmt->execute([
            $newQuantity,
            (int) $existingItem["cart_item_id"],
            $cart_id
        ]);


    } else {


        /* =================================================
           INSERT NEW PRODUCT
        ================================================= */

        $quantityToInsert =
            min($quantity, $stock);


        $insertStmt = $pdo->prepare("
            INSERT INTO cart_items
            (
                cart_id,
                product_id,
                quantity
            )
            VALUES (?, ?, ?)
        ");

        $insertStmt->execute([
            $cart_id,
            $product_id,
            $quantityToInsert
        ]);
    }


    /* -----------------------------------------------------
       RETURN TO CART
    ----------------------------------------------------- */

    header(
        "Location: ../pages/cart.php?success=added"
    );

    exit;
}


/* =========================================================
   UPDATE CART ITEM
========================================================= */

if ($action === "update") {

    $cart_item_id = filter_input(
        INPUT_POST,
        "cart_item_id",
        FILTER_VALIDATE_INT
    );

    $quantity = filter_input(
        INPUT_POST,
        "quantity",
        FILTER_VALIDATE_INT
    );


    /* -----------------------------------------------------
       VALIDATE CART ITEM
    ----------------------------------------------------- */

    if (!$cart_item_id || $cart_item_id <= 0) {

        header(
            "Location: ../pages/cart.php?error=item_not_found"
        );

        exit;
    }


    if (!$quantity || $quantity < 1) {
        $quantity = 1;
    }


    /* =====================================================
       VERIFY CART ITEM BELONGS TO CUSTOMER
    ===================================================== */

    $itemStmt = $pdo->prepare("
        SELECT
            ci.cart_item_id,
            ci.product_id,
            p.stock

        FROM cart_items ci

        INNER JOIN cart c
            ON ci.cart_id = c.cart_id

        INNER JOIN products p
            ON ci.product_id = p.product_id

        WHERE ci.cart_item_id = ?
          AND c.user_id = ?

        LIMIT 1
    ");

    $itemStmt->execute([
        $cart_item_id,
        $user_id
    ]);

    $item = $itemStmt->fetch();


    if (!$item) {

        header(
            "Location: ../pages/cart.php?error=item_not_found"
        );

        exit;
    }


    /* -----------------------------------------------------
       GET STOCK
    ----------------------------------------------------- */

    $stock = (int) $item["stock"];


    if ($stock <= 0) {

        header(
            "Location: ../pages/cart.php?error=out_of_stock"
        );

        exit;
    }


    /* -----------------------------------------------------
       LIMIT QUANTITY
    ----------------------------------------------------- */

    if ($quantity > $stock) {
        $quantity = $stock;
    }


    /* =====================================================
       UPDATE DATABASE
    ===================================================== */

    $updateStmt = $pdo->prepare("
        UPDATE cart_items
        SET quantity = ?
        WHERE cart_item_id = ?
    ");

    $updateStmt->execute([
        $quantity,
        $cart_item_id
    ]);


    header(
        "Location: ../pages/cart.php?success=updated"
    );

    exit;
}


/* =========================================================
   REMOVE CART ITEM
========================================================= */

if ($action === "remove") {

    $cart_item_id = filter_input(
        INPUT_POST,
        "cart_item_id",
        FILTER_VALIDATE_INT
    );


    /* -----------------------------------------------------
       VALIDATE ID
    ----------------------------------------------------- */

    if (!$cart_item_id || $cart_item_id <= 0) {

        header(
            "Location: ../pages/cart.php?error=item_not_found"
        );

        exit;
    }


    /* =====================================================
       DELETE ONLY CUSTOMER'S OWN ITEM
    ===================================================== */

    $deleteStmt = $pdo->prepare("
        DELETE ci

        FROM cart_items ci

        INNER JOIN cart c
            ON ci.cart_id = c.cart_id

        WHERE ci.cart_item_id = ?
          AND c.user_id = ?
    ");

    $deleteStmt->execute([
        $cart_item_id,
        $user_id
    ]);


    /* -----------------------------------------------------
       CHECK IF ITEM WAS ACTUALLY DELETED
    ----------------------------------------------------- */

    if ($deleteStmt->rowCount() === 0) {

        header(
            "Location: ../pages/cart.php?error=item_not_found"
        );

        exit;
    }


    header(
        "Location: ../pages/cart.php?success=removed"
    );

    exit;
}


/* =========================================================
   CLEAR CART
========================================================= */

if ($action === "clear") {

    $deleteStmt = $pdo->prepare("
        DELETE ci

        FROM cart_items ci

        INNER JOIN cart c
            ON ci.cart_id = c.cart_id

        WHERE c.user_id = ?
    ");

    $deleteStmt->execute([
        $user_id
    ]);


    header(
        "Location: ../pages/cart.php?success=cleared"
    );

    exit;
}


/* =========================================================
   INVALID ACTION
========================================================= */

header(
    "Location: ../pages/cart.php?error=invalid_action"
);

exit;