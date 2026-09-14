<?php

session_start();
require_once "../db.php";

/* =========================================================
   ADMIN ACCESS CHECK
========================================================= */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: ../pages/login.php");
    exit;
}


/* =========================================================
   VERIFY ADMIN ROLE
========================================================= */

$user_id = $_SESSION["user_id"];

$userStmt = $pdo->prepare("
    SELECT role
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$userStmt->execute([$user_id]);

$currentUser = $userStmt->fetch();

if (
    !$currentUser ||
    $currentUser["role"] !== "admin"
) {
    die("Access denied.");
}


/* =========================================================
   GET FORM DATA
========================================================= */

$order_id = filter_input(
    INPUT_POST,
    "order_id",
    FILTER_VALIDATE_INT
);

$payment_status = trim(
    $_POST["payment_status"] ?? ""
);

$customer_id = filter_input(
    INPUT_POST,
    "customer_id",
    FILTER_VALIDATE_INT
);


/* =========================================================
   VALIDATION
========================================================= */

$allowedStatuses = [
    "Pending",
    "Paid",
    "Failed",
    "Cancelled"
];

if (
    !$order_id ||
    !in_array(
        $payment_status,
        $allowedStatuses,
        true
    )
) {
    header(
        "Location: " .
        (
            $customer_id
                ? "customer-orders.php?user_id=" . $customer_id
                : "admin-dashboard.php"
        )
    );
    exit;
}


/* =========================================================
   GET PAYMENT
========================================================= */

$paymentStmt = $pdo->prepare("
    SELECT
        payment_id,
        order_id,
        payment_method,
        payment_status
    FROM payments
    WHERE order_id = ?
    LIMIT 1
");

$paymentStmt->execute([
    $order_id
]);

$payment = $paymentStmt->fetch();


/* =========================================================
   PAYMENT NOT FOUND
========================================================= */

if (!$payment) {
    die("Payment record not found.");
}


/* =========================================================
   ONLY ALLOW GCASH PAYMENT STATUS UPDATE
========================================================= */

if (
    strtolower(
        trim($payment["payment_method"])
    ) !== "gcash"
) {
    die("Payment status can only be updated for GCash payments.");
}


/* =========================================================
   UPDATE PAYMENT STATUS
========================================================= */

if ($payment_status === "Paid") {

    $updateStmt = $pdo->prepare("
        UPDATE payments
        SET
            payment_status = ?,
            paid_at = NOW()
        WHERE payment_id = ?
    ");

    $updateStmt->execute([
        $payment_status,
        $payment["payment_id"]
    ]);

} else {

    $updateStmt = $pdo->prepare("
        UPDATE payments
        SET
            payment_status = ?,
            paid_at = NULL
        WHERE payment_id = ?
    ");

    $updateStmt->execute([
        $payment_status,
        $payment["payment_id"]
    ]);
}


/* =========================================================
   REDIRECT BACK
========================================================= */

if ($customer_id) {

    header(
        "Location: customer-orders.php?user_id=" .
        $customer_id
    );

} else {

    header(
        "Location: admin-dashboard.php"
    );
}

exit;