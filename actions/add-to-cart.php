<?php

session_start();
require_once "../db.php";

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$product_id = filter_input(
    INPUT_POST,
    "product_id",
    FILTER_VALIDATE_INT
);

if (!$product_id) {
    header("Location: ../index.php");
    exit;
}

$productStmt = $pdo->prepare("
    SELECT
        product_id,
        product_name,
        stock
    FROM products
    WHERE product_id = ?
      AND status = 'active'
    LIMIT 1
");

$productStmt->execute([
    $product_id
]);

$product = $productStmt->fetch();

if (!$product) {
    header("Location: ../index.php");
    exit;
}

if ((int)$product["stock"] <= 0) {

    die(
        htmlspecialchars(
            $product["product_name"]
        ) . " is currently out of stock."
    );
}

/* Find the user's cart */
$cartStmt = $pdo->prepare("
    SELECT cart_id
    FROM cart
    WHERE user_id = ?
    LIMIT 1
");

$cartStmt->execute([$user_id]);

$cart = $cartStmt->fetch();

if ($cart) {

    $cart_id = $cart["cart_id"];

} else {

    /* Create a cart for the user */
    $createCart = $pdo->prepare("
        INSERT INTO cart (user_id)
        VALUES (?)
    ");

    $createCart->execute([$user_id]);

    $cart_id = $pdo->lastInsertId();
}

/* Check if product is already in cart */
$itemStmt = $pdo->prepare("
    SELECT cart_item_id, quantity
    FROM cart_items
    WHERE cart_id = ?
      AND product_id = ?
    LIMIT 1
");

$itemStmt->execute([
    $cart_id,
    $product_id
]);

$item = $itemStmt->fetch();

if ($item) {

    /* Increase quantity */
    $updateStmt = $pdo->prepare("
        UPDATE cart_items
        SET quantity = quantity + 1
        WHERE cart_item_id = ?
    ");

    $updateStmt->execute([
        $item["cart_item_id"]
    ]);

} else {

    /* Add new product */
    $insertStmt = $pdo->prepare("
        INSERT INTO cart_items
        (cart_id, product_id, quantity)
        VALUES (?, ?, 1)
    ");

    $insertStmt->execute([
        $cart_id,
        $product_id
    ]);
}

/* Go to cart */
header("Location: cart.php");
exit;