<?php

session_start();

require_once "../db.php";


/* =====================================================
   CHECK LOGIN
===================================================== */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: ../pages/login.php");
    exit;
}


/* =====================================================
   GET USER ID
===================================================== */

$user_id = (int)($_SESSION["user_id"] ?? 0);

if ($user_id <= 0) {
    header("Location: ../pages/login.php");
    exit;
}


/* =====================================================
   GET PRODUCT ID
===================================================== */

$product_id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);


/* =====================================================
   INVALID PRODUCT ID
===================================================== */

if (!$product_id || $product_id <= 0) {

    header("Location: ../pages/wishlist.php");
    exit;
}


/* =====================================================
   REMOVE WISHLIST ITEM
===================================================== */

try {

    $delete = $pdo->prepare("
        DELETE FROM wishlist
        WHERE user_id = ?
          AND product_id = ?
    ");

    $delete->execute([
        $user_id,
        $product_id
    ]);


    /* =================================================
       RETURN TO WISHLIST
    ================================================= */

    header("Location: ../pages/wishlist.php");
    exit;


} catch (PDOException $e) {

    /* =================================================
       IF DATABASE ERROR
    ================================================= */

    header("Location: ../pages/wishlist.php");
    exit;
}