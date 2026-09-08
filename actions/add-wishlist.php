<?php

session_start();
require_once "../db.php";

// User must be logged in
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// Get product name from the request
$product_name = trim($_GET["product_name"] ?? "");

if ($product_name === "") {
    header("Location: ../index.php");
    exit;
}

// Find the product in the database
$stmt = $pdo->prepare("
    SELECT product_id
    FROM products
    WHERE product_name = ?
    LIMIT 1
");

$stmt->execute([$product_name]);

$product = $stmt->fetch();

if (!$product) {
    header("Location: ../index.php");
    exit;
}

$product_id = $product["product_id"];

// Check if already in wishlist
$check = $pdo->prepare("
    SELECT wishlist_id
    FROM wishlist
    WHERE user_id = ?
      AND product_id = ?
    LIMIT 1
");

$check->execute([
    $user_id,
    $product_id
]);

$existing = $check->fetch();

if (!$existing) {

    $stmt = $pdo->prepare("
        INSERT INTO wishlist
        (user_id, product_id)
        VALUES (?, ?)
    ");

    $stmt->execute([
        $user_id,
        $product_id
    ]);
}

// Return to the page where the user clicked the heart
$redirect = $_SERVER["HTTP_REFERER"] ?? "../index.php";

header("Location: " . $redirect);
exit;