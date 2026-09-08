<?php

/**
 * BrightBuy - Update Cart Item
 *
 * Customer-side UPDATE operation.
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
   USER ID
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

    header("Location: ../pages/cart.php");
    exit;
}


/* =========================================================
   GET CART ITEM ID
========================================================= */

$cart_item_id = filter_input(
    INPUT_POST,
    "cart_item_id",
    FILTER_VALIDATE_INT
);


/* =========================================================
   GET QUANTITY
========================================================= */

$quantity = filter_input(
    INPUT_POST,
    "quantity",
    FILTER_VALIDATE_INT
);


/* =========================================================
   VALIDATE INPUT
========================================================= */

if (!$cart_item_id || $cart_item_id <= 0) {

    $_SESSION["cart_error"] =
        "Invalid cart item.";

    header("Location: ../pages/cart.php");
    exit;
}


if ($quantity === false || $quantity === null) {

    $_SESSION["cart_error"] =
        "Invalid quantity.";

    header("Location: ../pages/cart.php");
    exit;
}


/* =========================================================
   IF QUANTITY IS ZERO
   REMOVE ITEM
========================================================= */

if ($quantity <= 0) {

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

    $_SESSION["cart_success"] =
        "Item removed from your cart.";

    header("Location: ../pages/cart.php");
    exit;
}


/* =========================================================
   GET CART ITEM + PRODUCT STOCK
========================================================= */

$itemStmt = $pdo->prepare("
    SELECT
        ci.cart_item_id,
        ci.cart_id,
        ci.product_id,
        ci.quantity,
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

$itemStmt->execute([
    $cart_item_id,
    $user_id
]);

$item = $itemStmt->fetch();


/* =========================================================
   CHECK ITEM EXISTS
========================================================= */

if (!$item) {

    $_SESSION["cart_error"] =
        "Cart item not found.";

    header("Location: ../pages/cart.php");
    exit;
}


/* =========================================================
   CHECK STOCK
========================================================= */

$stock = (int)$item["stock"];

if ($quantity > $stock) {

    $_SESSION["cart_error"] =
        "Only " . $stock .
        " unit(s) of " .
        $item["product_name"] .
        " are available.";

    header("Location: ../pages/cart.php");
    exit;
}


/* =========================================================
   UPDATE QUANTITY
========================================================= */

$updateStmt = $pdo->prepare("
    UPDATE cart_items ci
    INNER JOIN cart c
        ON ci.cart_id = c.cart_id
    SET ci.quantity = ?
    WHERE ci.cart_item_id = ?
      AND c.user_id = ?
");

$updateStmt->execute([
    $quantity,
    $cart_item_id,
    $user_id
]);


/* =========================================================
   SUCCESS
========================================================= */

$_SESSION["cart_success"] =
    "Cart updated successfully.";


/* =========================================================
   REDIRECT
========================================================= */

header("Location: ../pages/cart.php");
exit;