<?php

/**
 * BrightBuy Delete Product Action
 *
 * Handles deletion of a product from the database.
 * Database operations are handled through
 * reusable product functions.
 */

require_once "../db.php";
require_once "../includes/auth.php";
require_once "../includes/product-functions.php";


/* =====================================================
   ADMIN ACCESS
===================================================== */

requireAdmin($pdo, "../admin/admin-login.php");


/* =====================================================
   ONLY ALLOW POST REQUEST
===================================================== */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../admin/admin-products.php");
    exit;
}


/* =====================================================
   GET PRODUCT ID
===================================================== */

$productId = filter_input(
    INPUT_POST,
    "product_id",
    FILTER_VALIDATE_INT
);

if ($productId === false || $productId === null || $productId <= 0) {

    $_SESSION["product_errors"] = [
        "Invalid product ID."
    ];

    header("Location: ../admin/admin-products.php");
    exit;
}


/* =====================================================
   CHECK IF PRODUCT EXISTS
===================================================== */

$product = getProductById($pdo, $productId);

if (!$product) {

    $_SESSION["product_errors"] = [
        "Product not found."
    ];

    header("Location: ../admin/admin-products.php");
    exit;
}


/* =====================================================
   DELETE PRODUCT
===================================================== */

try {

    $deleted = deleteProduct(
        $pdo,
        $productId
    );

    if (!$deleted) {

        throw new PDOException(
            "Product deletion failed."
        );
    }


    /* =================================================
       DELETE PRODUCT IMAGE
    ================================================== */

    if (!empty($product["image"])) {

        $imagePath =
            "../"
            . $product["image"];

        if (
            file_exists($imagePath) &&
            is_file($imagePath)
        ) {

            unlink($imagePath);
        }
    }


    /* =================================================
       SUCCESS MESSAGE
    ================================================= */

    $_SESSION["product_success"] =
        "Product deleted successfully.";


    header(
        "Location: ../admin/admin-products.php"
    );

    exit;


} catch (PDOException $e) {

    /* =================================================
       DATABASE ERROR
    ================================================== */

    $_SESSION["product_errors"] = [
        "Unable to delete product. It may be associated with existing orders."
    ];


    header(
        "Location: ../admin/admin-products.php"
    );

    exit;
}