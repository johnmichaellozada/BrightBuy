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
   CHECK CURRENT USER ROLE FROM DATABASE
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

$order_id = filter_input(
    INPUT_POST,
    "order_id",
    FILTER_VALIDATE_INT
);

$status = trim($_POST["status"] ?? "");


/* =====================================================
   VALIDATE
===================================================== */

$allowed_statuses = [
    "Pending",
    "Processing",
    "Shipped",
    "Delivered",
    "Cancelled"
];

if (
    !$order_id ||
    !in_array($status, $allowed_statuses, true)
) {
    header("Location: admin-dashboard.php");
    exit;
}


/* =====================================================
   UPDATE ORDER
===================================================== */

$stmt = $pdo->prepare("
    UPDATE orders
    SET status = ?
    WHERE order_id = ?
");

$stmt->execute([
    $status,
    $order_id
]);


/* =====================================================
   RETURN TO ADMIN DASHBOARD
===================================================== */

header("Location: admin-dashboard.php");
exit;