<?php

session_start();
require_once "../db.php";

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$cart_item_id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$cart_item_id) {
    header("Location: cart.php");
    exit;
}

/* Delete only if the item belongs to the logged-in user's cart */
$stmt = $pdo->prepare("
    DELETE ci
    FROM cart_items ci
    INNER JOIN cart c
        ON ci.cart_id = c.cart_id
    WHERE ci.cart_item_id = ?
      AND c.user_id = ?
");

$stmt->execute([
    $cart_item_id,
    $user_id
]);

header("Location: cart.php");
exit;