<?php

/**
 * BrightBuy - Cancel Order Action
 *
 * Customer can:
 * - Cancel their own Pending order
 * - Restore the purchased product stock
 *
 * File location:
 * /BrightBuy/actions/cancel-order.php
 */

session_start();

require_once "../db.php";


/* =========================================================
   CUSTOMER LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: ../pages/login.php");
    exit;
}


$user_id = (int) $_SESSION["user_id"];


/* =========================================================
   GET ORDER ID
========================================================= */

$order_id = filter_input(
    INPUT_POST,
    "order_id",
    FILTER_VALIDATE_INT
);


if (!$order_id) {
    header("Location: ../pages/my-orders.php?error=invalid_order");
    exit;
}


try {

    /* =====================================================
       START TRANSACTION
    ===================================================== */

    $pdo->beginTransaction();


    /* =====================================================
       GET CUSTOMER ORDER
       LOCK THE ORDER DURING TRANSACTION
    ===================================================== */

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

    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);


    /* =====================================================
       ORDER NOT FOUND
    ===================================================== */

    if (!$order) {

        $pdo->rollBack();

        header(
            "Location: ../pages/my-orders.php?error=order_not_found"
        );

        exit;
    }


    /* =====================================================
       ONLY PENDING ORDERS CAN BE CANCELLED
    ===================================================== */

    if ($order["status"] !== "Pending") {

        $pdo->rollBack();

        header(
            "Location: ../pages/my-orders.php?error=cannot_cancel"
        );

        exit;
    }


    /* =====================================================
       GET ORDER ITEMS
    ===================================================== */

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

    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);


    /* =====================================================
       RESTORE PRODUCT STOCK
    ===================================================== */

    foreach ($items as $item) {

        $product_id = (int) $item["product_id"];
        $quantity = (int) $item["quantity"];


        if ($product_id <= 0 || $quantity <= 0) {
            continue;
        }


        $stockStmt = $pdo->prepare("
            UPDATE products
            SET stock = stock + ?
            WHERE product_id = ?
        ");

        $stockStmt->execute([
            $quantity,
            $product_id
        ]);
    }


    /* =====================================================
       CHANGE ORDER STATUS TO CANCELLED
    ===================================================== */

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


    /* =====================================================
       VERIFY STATUS WAS ACTUALLY UPDATED
    ===================================================== */

    if ($updateStmt->rowCount() !== 1) {

        throw new Exception(
            "Order could not be cancelled."
        );
    }


    /* =====================================================
       COMPLETE TRANSACTION
    ===================================================== */

    $pdo->commit();


    /* =====================================================
       RETURN TO MY ORDERS
    ===================================================== */

    header(
        "Location: ../pages/my-orders.php?success=cancelled"
    );

    exit;


} catch (Exception $e) {

    /* =====================================================
       ROLLBACK IF SOMETHING FAILED
    ===================================================== */

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    header(
        "Location: ../pages/my-orders.php?error=cancel_failed"
    );

    exit;
}