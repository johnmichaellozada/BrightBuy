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
   GET CUSTOMER ID
===================================================== */

$customer_id = filter_input(
    INPUT_POST,
    "customer_id",
    FILTER_VALIDATE_INT
);


/* =====================================================
   VALIDATE ORDER ID AND STATUS
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

    if ($customer_id) {
        header(
            "Location: customer-orders.php?user_id="
            . $customer_id
        );
    } else {
        header("Location: admin-dashboard.php");
    }

    exit;
}


/* =====================================================
   GET CURRENT ORDER STATUS
===================================================== */

$orderStmt = $pdo->prepare("
    SELECT
        order_id,
        status
    FROM orders
    WHERE order_id = ?
    LIMIT 1
");

$orderStmt->execute([
    $order_id
]);

$order = $orderStmt->fetch();


/* =====================================================
   ORDER NOT FOUND
===================================================== */

if (!$order) {

    if ($customer_id) {
        header(
            "Location: customer-orders.php?user_id="
            . $customer_id
        );
    } else {
        header("Location: admin-dashboard.php");
    }

    exit;
}


/* =====================================================
   PREVENT CHANGING A DELIVERED ORDER
===================================================== */

$currentStatus = trim(
    $order["status"] ?? ""
);


/*
    Once an order is Delivered,
    it is considered COMPLETED.

    It can no longer be changed
    to any other status.
*/

if (
    strtolower($currentStatus) === "delivered"
) {

    if ($customer_id) {

        header(
            "Location: customer-orders.php?user_id="
            . $customer_id
            . "&error=order_completed"
        );

    } else {

        header(
            "Location: admin-dashboard.php?error=order_completed"
        );

    }

    exit;
}


/* =====================================================
   UPDATE ORDER STATUS
===================================================== */

$stmt = $pdo->prepare("
    UPDATE orders
    SET status = ?
    WHERE order_id = ?
      AND LOWER(TRIM(status)) <> 'delivered'
");


$stmt->execute([
    $status,
    $order_id
]);


/* =====================================================
   CHECK IF UPDATE WAS SUCCESSFUL
===================================================== */

if ($stmt->rowCount() > 0) {

    /*
        Status successfully changed.
    */

    if ($customer_id) {

        header(
            "Location: customer-orders.php?user_id="
            . $customer_id
            . "&success=status_updated"
        );

    } else {

        header(
            "Location: admin-dashboard.php?success=status_updated"
        );

    }

    exit;
}


/* =====================================================
   UPDATE FAILED
===================================================== */

if ($customer_id) {

    header(
        "Location: customer-orders.php?user_id="
        . $customer_id
        . "&error=status_update_failed"
    );

} else {

    header(
        "Location: admin-dashboard.php?error=status_update_failed"
    );

}

exit;