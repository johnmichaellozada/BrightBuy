<?php

/**
 * BrightBuy Add Product Action
 *
 * Processes the Add Product form.
 * All database operations use prepared statements.
 */

require_once "../db.php";
require_once "../includes/auth.php";
require_once "../includes/product-functions.php";

requireAdmin($pdo, "../admin/admin-login.php");


/* =====================================================
   ONLY ALLOW POST REQUEST
===================================================== */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../admin/admin-products.php");
    exit;
}


/* =====================================================
   GET FORM DATA
===================================================== */

$productName = trim($_POST["product_name"] ?? "");

$description = trim($_POST["description"] ?? "");

$categoryId = $_POST["category_id"] ?? null;

$price = $_POST["price"] ?? "";

$stock = $_POST["stock"] ?? "";

$status = trim($_POST["status"] ?? "active");


/* =====================================================
   VALIDATION
===================================================== */

$errors = [];


/* PRODUCT NAME */

if ($productName === "") {

    $errors[] = "Product name is required.";

}


/* PRICE */

if ($price === "") {

    $errors[] = "Price is required.";

} elseif (!is_numeric($price) || (float)$price < 0) {

    $errors[] = "Please enter a valid price.";

}


/* STOCK */

if ($stock === "") {

    $errors[] = "Stock is required.";

} elseif (
    filter_var(
        $stock,
        FILTER_VALIDATE_INT
    ) === false ||
    (int)$stock < 0
) {

    $errors[] = "Please enter a valid stock quantity.";

}


/* CATEGORY */

if ($categoryId !== "" && $categoryId !== null) {

    if (
        filter_var(
            $categoryId,
            FILTER_VALIDATE_INT
        ) === false ||
        (int)$categoryId <= 0
    ) {

        $errors[] = "Please select a valid category.";

    } else {

        $categoryId = (int)$categoryId;

    }

} else {

    $categoryId = null;

}


/* STATUS */

$allowedStatuses = [
    "active",
    "inactive"
];

if (!in_array($status, $allowedStatuses, true)) {

    $errors[] = "Invalid product status.";

}


/* =====================================================
   IMAGE
===================================================== */

$imagePath = "";


if (
    isset($_FILES["image"]) &&
    $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
) {

    if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {

        $errors[] = "There was an error uploading the image.";

    } else {

        $allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        $fileType = mime_content_type(
            $_FILES["image"]["tmp_name"]
        );

        if (!in_array($fileType, $allowedTypes, true)) {

            $errors[] = "Only JPG, PNG, and WEBP images are allowed.";

        }

    }

}


/* =====================================================
   STOP IF VALIDATION FAILED
===================================================== */

if (!empty($errors)) {

    $_SESSION["product_errors"] = $errors;

    header("Location: ../admin/add-product.php");

    exit;
}


/* =====================================================
   HANDLE IMAGE UPLOAD
===================================================== */

if (
    isset($_FILES["image"]) &&
    $_FILES["image"]["error"] === UPLOAD_ERR_OK
) {

    $uploadDirectory = "../images/products/";


    if (!is_dir($uploadDirectory)) {

        mkdir(
            $uploadDirectory,
            0755,
            true
        );

    }


    $extension = strtolower(
        pathinfo(
            $_FILES["image"]["name"],
            PATHINFO_EXTENSION
        )
    );


    $fileName =
        uniqid("product_", true)
        . "."
        . $extension;


    $destination =
        $uploadDirectory
        . $fileName;


    if (
        !move_uploaded_file(
            $_FILES["image"]["tmp_name"],
            $destination
        )
    ) {

        $_SESSION["product_errors"] = [
            "Unable to upload product image."
        ];

        header("Location: ../admin/add-product.php");

        exit;
    }


    $imagePath =
        "images/products/"
        . $fileName;
}


/* =====================================================
   CREATE PRODUCT
===================================================== */

try {

    createProduct(
        $pdo,
        $productName,
        $description,
        (float)$price,
        (int)$stock,
        $categoryId,
        $imagePath,
        $status
    );


    /* =============================================
       SUCCESS
    ============================================= */

    $_SESSION["product_success"] =
        "Product added successfully.";


    header("Location: ../admin/admin-products.php");

    exit;


} catch (PDOException $e) {

    /* =============================================
       REMOVE UPLOADED IMAGE IF DATABASE FAILED
    ============================================= */

    if ($imagePath !== "") {

        $uploadedFile =
            "../"
            . $imagePath;

        if (file_exists($uploadedFile)) {

            unlink($uploadedFile);

        }
    }


    $_SESSION["product_errors"] = [
        "Unable to add product. Please try again."
    ];


    header("Location: ../admin/add-product.php");

    exit;
}