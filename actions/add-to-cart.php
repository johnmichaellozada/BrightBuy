<?php

/**
 * BrightBuy - Add Product to Cart
 *
 * Customer-side CREATE operation.
 */

session_start();

require_once "../db.php";


/* =========================================================
   CHECK LOGIN
========================================================= */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {

    header("Location: ../pages/login.php");
    exit;
}


/* =========================================================
   GET USER ID
========================================================= */

$user_id = (int)($_SESSION["user_id"] ?? 0);

if ($user_id <= 0) {

    header("Location: ../pages/login.php");
    exit;
}


/* =========================================================
   ONLY ALLOW POST
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../pages/shop.php");
    exit;
}


/* =========================================================
   GET PRODUCT ID
========================================================= */

$product_id = filter_input(
    INPUT_POST,
    "product_id",
    FILTER_VALIDATE_INT
);


/* =========================================================
   VALIDATE PRODUCT ID
========================================================= */

if (!$product_id || $product_id <= 0) {

    $_SESSION["cart_error"] = "Invalid product ID.";

    header("Location: ../pages/shop.php");
    exit;
}


/* =========================================================
   CHECK PRODUCT
========================================================= */

$productStmt = $pdo->prepare("
    SELECT
        product_id,
        product_name,
        price,
        stock,
        status
    FROM products
    WHERE product_id = ?
    LIMIT 1
");

$productStmt->execute([
    $product_id
]);

$product = $productStmt->fetch();


if (!$product) {

    $_SESSION["cart_error"] = "Product not found.";

    header("Location: ../pages/shop.php");
    exit;
}


/* =========================================================
   CHECK PRODUCT STATUS
========================================================= */

if (
    isset($product["status"]) &&
    strtolower($product["status"]) !== "active"
) {

    $_SESSION["cart_error"] =
        "This product is currently unavailable.";

    header("Location: ../pages/shop.php");
    exit;
}


/* =========================================================
   CHECK STOCK
========================================================= */

$stock = (int)$product["stock"];

if ($stock <= 0) {

    $_SESSION["cart_error"] =
        "This product is out of stock.";

    header("Location: ../pages/shop.php");
    exit;
}


/* =========================================================
   GET OR CREATE CUSTOMER CART
========================================================= */

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


/* =========================================================
   CREATE CART IF NONE EXISTS
========================================================= */

if (!$cart) {

    $createCartStmt = $pdo->prepare("
        INSERT INTO cart (user_id)
        VALUES (?)
    ");

    $createCartStmt->execute([
        $user_id
    ]);

    $cart_id = (int)$pdo->lastInsertId();

} else {

    $cart_id = (int)$cart["cart_id"];
}


/* =========================================================
   CHECK IF PRODUCT ALREADY EXISTS IN CART
========================================================= */

$itemStmt = $pdo->prepare("
    SELECT
        cart_item_id,
        quantity
    FROM cart_items
    WHERE cart_id = ?
      AND product_id = ?
    LIMIT 1
");

$itemStmt->execute([
    $cart_id,
    $product_id
]);

$existingItem = $itemStmt->fetch();


/* =========================================================
   ADD OR INCREASE QUANTITY
========================================================= */

if ($existingItem) {

    $newQuantity =
        (int)$existingItem["quantity"] + 1;


    /* ---------------------------------------------
       DO NOT EXCEED STOCK
    --------------------------------------------- */

    if ($newQuantity > $stock) {

        $_SESSION["cart_error"] =
            "You cannot add more than the available stock.";

        header("Location: ../pages/cart.php");
        exit;
    }


    $updateStmt = $pdo->prepare("
        UPDATE cart_items
        SET quantity = ?
        WHERE cart_item_id = ?
          AND cart_id = ?
    ");

    $updateStmt->execute([
        $newQuantity,
        (int)$existingItem["cart_item_id"],
        $cart_id
    ]);

} else {

    /* ---------------------------------------------
       INSERT NEW CART ITEM
    --------------------------------------------- */

    $insertStmt = $pdo->prepare("
        INSERT INTO cart_items
        (
            cart_id,
            product_id,
            quantity
        )
        VALUES
        (
            ?,
            ?,
            1
        )
    ");

    $insertStmt->execute([
        $cart_id,
        $product_id
    ]);
}


/* =========================================================
   SUCCESS
========================================================= */

$_SESSION["cart_success"] =
    htmlspecialchars($product["product_name"])
    . " added to your cart.";


/* =========================================================
   REDIRECT TO CART
========================================================= */

header("Location: ../pages/cart.php");
exit;