<?php

session_start();

require_once "../db.php";

header("Content-Type: application/json; charset=UTF-8");


/* =====================================================
   CHECK LOGIN
===================================================== */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {

    echo json_encode([
        "success" => false,
        "logged_in" => false,
        "in_wishlist" => false,
        "message" => "Please log in first."
    ]);

    exit;
}


/* =====================================================
   GET USER ID
===================================================== */

$user_id =
    (int)($_SESSION["user_id"] ?? 0);


if ($user_id <= 0) {

    echo json_encode([
        "success" => false,
        "logged_in" => false,
        "in_wishlist" => false,
        "message" => "Invalid user session."
    ]);

    exit;
}


/* =====================================================
   GET PRODUCT ID
===================================================== */

$product_id =
    filter_input(
        INPUT_POST,
        "product_id",
        FILTER_VALIDATE_INT
    );


if (!$product_id || $product_id <= 0) {

    echo json_encode([
        "success" => false,
        "logged_in" => true,
        "in_wishlist" => false,
        "message" => "Invalid product."
    ]);

    exit;
}


/* =====================================================
   CHECK PRODUCT EXISTS
===================================================== */

$productCheck =
    $pdo->prepare("
        SELECT product_id
        FROM products
        WHERE product_id = ?
        LIMIT 1
    ");


$productCheck->execute([
    $product_id
]);


if (!$productCheck->fetch()) {

    echo json_encode([
        "success" => false,
        "logged_in" => true,
        "in_wishlist" => false,
        "message" => "Product not found."
    ]);

    exit;
}


/* =====================================================
   CHECK EXISTING WISHLIST
===================================================== */

$check =
    $pdo->prepare("
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


$wishlistItem =
    $check->fetch(
        PDO::FETCH_ASSOC
    );


/* =====================================================
   REMOVE FROM WISHLIST
===================================================== */

if ($wishlistItem) {

    $delete =
        $pdo->prepare("
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
        "logged_in" => true,
        "in_wishlist" => false,
        "message" => "Removed from wishlist."
    ]);

    exit;
}


/* =====================================================
   ADD TO WISHLIST
===================================================== */

try {

    $insert =
        $pdo->prepare("
            INSERT INTO wishlist
            (
                user_id,
                product_id
            )
            VALUES
            (
                ?,
                ?
            )
        ");


    $insert->execute([
        $user_id,
        $product_id
    ]);


    echo json_encode([
        "success" => true,
        "logged_in" => true,
        "in_wishlist" => true,
        "message" => "Added to wishlist."
    ]);

    exit;

} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "logged_in" => true,
        "in_wishlist" => false,
        "message" => "Unable to add product to wishlist."
    ]);

    exit;
}