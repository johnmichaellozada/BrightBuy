<?php

session_start();
require_once "../db.php";

// User must be logged in
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$wishlist_id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$wishlist_id) {
    header("Location: wishlist.php");
    exit;
}

// Delete only the current user's wishlist item
$stmt = $pdo->prepare("
    DELETE FROM wishlist
    WHERE wishlist_id = ?
      AND user_id = ?
");

$stmt->execute([
    $wishlist_id,
    $user_id
]);

header("Location: wishlist.php");
exit;