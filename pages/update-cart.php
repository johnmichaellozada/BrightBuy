<?php

session_start();
require_once "../db.php";

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

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

if (!$cart_item_id || $quantity === false || $quantity === null) {
    header("Location: cart.php");
    exit;
}


/* =====================================================
   FIND CART ITEM + CHECK PRODUCT STOCK
===================================================== */

$stmt = $pdo->prepare("
    SELECT
        ci.cart_item_id,
        p.product_name,
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

$stmt->execute([
    $cart_item_id,
    $user_id
]);

$item = $stmt->fetch();


/* =====================================================
   CART ITEM NOT FOUND
===================================================== */

if (!$item) {
    header("Location: cart.php");
    exit;
}


/* =====================================================
   MINIMUM QUANTITY
===================================================== */

if ($quantity < 1) {
    $quantity = 1;
}


/* =====================================================
   CHECK AVAILABLE STOCK
===================================================== */

$stock = (int)$item["stock"];

if ($stock <= 0) {

    header("Location: cart.php");
    exit;
}

if ($quantity > $stock) {

    $quantity = $stock;
}


/* =====================================================
   UPDATE CART QUANTITY
===================================================== */

$update = $pdo->prepare("
    UPDATE cart_items
    SET quantity = ?
    WHERE cart_item_id = ?
");

$update->execute([
    $quantity,
    $cart_item_id
]);


/* =====================================================
   RETURN TO CART
===================================================== */

header("Location: cart.php");
exit;