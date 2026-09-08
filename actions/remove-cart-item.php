<?php

/**
 * BrightBuy - Remove Cart Item
 *
 * Customer-side DELETE operation.
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


if (!$cart_item_id || $cart_item_id <= 0) {

    $_SESSION["cart_error"] =
        "Invalid cart item.";

    header("Location: ../pages/cart.php");
    exit;
}


/* =========================================================
   DELETE ONLY FROM CURRENT USER'S CART
========================================================= */

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


/* =========================================================
   CHECK RESULT
========================================================= */

if ($deleteStmt->rowCount() > 0) {

    $_SESSION["cart_success"] =
        "Item removed from your cart.";

} else {

    $_SESSION["cart_error"] =
        "Cart item not found.";
}


/* =========================================================
   REDIRECT
========================================================= */

header("Location: ../pages/cart.php");
exit;