<?php

session_start();
require_once "../db.php";


/* =====================================================
   ADMIN ACCESS
===================================================== */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}


/* =====================================================
   CHECK CURRENT USER ROLE
===================================================== */

$userStmt = $pdo->prepare("
    SELECT role
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$userStmt->execute([
    $_SESSION["user_id"]
]);

$currentUser = $userStmt->fetch();

if (
    !$currentUser ||
    $currentUser["role"] !== "admin"
) {
    die("Access denied.");
}


/* =====================================================
   GET FORM DATA
===================================================== */

$product_id = filter_input(
    INPUT_POST,
    "product_id",
    FILTER_VALIDATE_INT
);

$stock = filter_input(
    INPUT_POST,
    "stock",
    FILTER_VALIDATE_INT
);


/* =====================================================
   VALIDATE
===================================================== */

if (
    !$product_id ||
    $stock === false ||
    $stock < 0
) {
    header("Location: admin-products.php");
    exit;
}


/* =====================================================
   CHECK PRODUCT
===================================================== */

$productStmt = $pdo->prepare("
    SELECT product_id
    FROM products
    WHERE product_id = ?
    LIMIT 1
");

$productStmt->execute([
    $product_id
]);

if (!$productStmt->fetch()) {
    header("Location: admin-products.php");
    exit;
}


/* =====================================================
   UPDATE STOCK
===================================================== */

$updateStmt = $pdo->prepare("
    UPDATE products
    SET stock = ?
    WHERE product_id = ?
");

$updateStmt->execute([
    $stock,
    $product_id
]);


/* =====================================================
   RETURN
===================================================== */

header("Location: admin-products.php");
exit;