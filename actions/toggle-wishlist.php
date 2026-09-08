<?php

session_start();
require_once "../db.php";

header("Content-Type: application/json");

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    echo json_encode([
        "success" => false,
        "logged_in" => false,
        "message" => "Please log in first."
    ]);
    exit;
}

$user_id = $_SESSION["user_id"];

$product_id = filter_input(
    INPUT_POST,
    "product_id",
    FILTER_VALIDATE_INT
);

if (!$product_id) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid product."
    ]);
    exit;
}

// Check that the product exists
$productCheck = $pdo->prepare("
    SELECT product_id
    FROM products
    WHERE product_id = ?
    LIMIT 1
");

$productCheck->execute([$product_id]);

if (!$productCheck->fetch()) {
    echo json_encode([
        "success" => false,
        "message" => "Product not found."
    ]);
    exit;
}

// Check whether this product is already in the user's wishlist
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

$wishlistItem = $check->fetch();

if ($wishlistItem) {

    // Remove from wishlist
    $delete = $pdo->prepare("
        DELETE FROM wishlist
        WHERE wishlist_id = ?
          AND user_id = ?
    ");

    $delete->execute([
        $wishlistItem["wishlist_id"],
        $user_id
    ]);

    echo json_encode([
        "success" => true,
        "in_wishlist" => false,
        "message" => "Removed from wishlist."
    ]);

} else {

    // Add to wishlist
    $insert = $pdo->prepare("
        INSERT INTO wishlist
        (user_id, product_id)
        VALUES (?, ?)
    ");

    $insert->execute([
        $user_id,
        $product_id
    ]);

    echo json_encode([
        "success" => true,
        "in_wishlist" => true,
        "message" => "Added to wishlist."
    ]);
}