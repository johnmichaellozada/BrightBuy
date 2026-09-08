<?php

session_start();

require_once "../db.php";


/* =====================================================
   CUSTOMER ACCESS CHECK
===================================================== */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];


/* =====================================================
   GET ORDER ID
===================================================== */

$order_id = filter_input(
    INPUT_POST,
    "order_id",
    FILTER_VALIDATE_INT
);

if (!$order_id) {
    header("Location: my-orders.php");
    exit;
}


try {

    /* =================================================
       START TRANSACTION
    ================================================= */

    $pdo->beginTransaction();


    /* =================================================
       GET ORDER
       CUSTOMER CAN ONLY CANCEL THEIR OWN ORDER
    ================================================= */

    $orderStmt = $pdo->prepare("
        SELECT
            order_id,
            user_id,
            status
        FROM orders
        WHERE order_id = ?
          AND user_id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $orderStmt->execute([
        $order_id,
        $user_id
    ]);

    $order = $orderStmt->fetch();


    if (!$order) {

        $pdo->rollBack();

        header("Location: my-orders.php");
        exit;
    }


    /* =================================================
       ONLY PENDING ORDERS CAN BE CANCELLED
    ================================================= */

    if ($order["status"] !== "Pending") {

        $pdo->rollBack();

        header("Location: my-orders.php");
        exit;
    }


    /* =================================================
       GET ORDER ITEMS
    ================================================= */

    $itemsStmt = $pdo->prepare("
        SELECT
            product_id,
            quantity
        FROM order_items
        WHERE order_id = ?
    ");

    $itemsStmt->execute([
        $order_id
    ]);

    $items = $itemsStmt->fetchAll();


    /* =================================================
       RESTORE PRODUCT STOCK
    ================================================= */

    foreach ($items as $item) {

        $stockStmt = $pdo->prepare("
            UPDATE products
            SET stock = stock + ?
            WHERE product_id = ?
        ");

        $stockStmt->execute([
            (int)$item["quantity"],
            (int)$item["product_id"]
        ]);
    }


    /* =================================================
       CHANGE ORDER STATUS
    ================================================= */

    $updateStmt = $pdo->prepare("
        UPDATE orders
        SET status = 'Cancelled'
        WHERE order_id = ?
          AND user_id = ?
          AND status = 'Pending'
    ");

    $updateStmt->execute([
        $order_id,
        $user_id
    ]);


    /* =================================================
       COMPLETE TRANSACTION
    ================================================= */

    $pdo->commit();


    header("Location: my-orders.php");
    exit;


} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die(
        "Unable to cancel order. Please try again."
    );
}