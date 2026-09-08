<?php

/**
 * BrightBuy Edit Product Action
 *
 * Processes the Edit Product form.
 * Updates the product using reusable product functions.
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
   CHECK PRODUCT EXISTS
===================================================== */

$product = getProductById($pdo, (int)$productId);

if (!$product) {

    $_SESSION["product_errors"] = [
        "Product not found."
    ];

    header("Location: ../admin/admin-products.php");
    exit;
}


/* =====================================================
   GET FORM DATA
===================================================== */

$productName = trim(
    $_POST["product_name"] ?? ""
);

$description = trim(
    $_POST["description"] ?? ""
);

$categoryId = $_POST["category_id"] ?? "";

$price = $_POST["price"] ?? "";

$stock = $_POST["stock"] ?? "";

$status = strtolower(
    trim($_POST["status"] ?? "")
);


/* =====================================================
   VALIDATION
===================================================== */

$errors = [];


/* -----------------------------------------------------
   PRODUCT NAME
----------------------------------------------------- */

if ($productName === "") {

    $errors[] = "Product name is required.";

} elseif (strlen($productName) > 255) {

    $errors[] =
        "Product name must not exceed 255 characters.";
}


/* -----------------------------------------------------
   PRICE
----------------------------------------------------- */

if ($price === "") {

    $errors[] = "Price is required.";

} elseif (
    !is_numeric($price) ||
    (float)$price < 0
) {

    $errors[] =
        "Please enter a valid price.";
}


/* -----------------------------------------------------
   STOCK
----------------------------------------------------- */

if ($stock === "") {

    $errors[] = "Stock is required.";

} elseif (
    filter_var(
        $stock,
        FILTER_VALIDATE_INT
    ) === false ||
    (int)$stock < 0
) {

    $errors[] =
        "Please enter a valid stock quantity.";
}


/* -----------------------------------------------------
   CATEGORY
----------------------------------------------------- */

if ($categoryId === "" || $categoryId === null) {

    $categoryId = null;

} else {

    $validatedCategoryId = filter_var(
        $categoryId,
        FILTER_VALIDATE_INT
    );

    if (
        $validatedCategoryId === false ||
        $validatedCategoryId <= 0
    ) {

        $errors[] =
            "Please select a valid category.";

    } else {

        $categoryId = (int)$validatedCategoryId;
    }
}


/* -----------------------------------------------------
   STATUS
----------------------------------------------------- */

$allowedStatuses = [
    "active",
    "inactive"
];

if (!in_array(
    $status,
    $allowedStatuses,
    true
)) {

    $errors[] =
        "Invalid product status.";
}


/* =====================================================
   IMAGE VALIDATION
===================================================== */

$newImagePath = null;

$hasNewImage = (
    isset($_FILES["image"]) &&
    $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
);


if ($hasNewImage) {

    /* -------------------------------------------------
       CHECK UPLOAD ERROR
    ------------------------------------------------- */

    if (
        $_FILES["image"]["error"] !==
        UPLOAD_ERR_OK
    ) {

        $errors[] =
            "There was an error uploading the image.";

    } else {

        /* ---------------------------------------------
           CHECK FILE SIZE
        --------------------------------------------- */

        if (
            $_FILES["image"]["size"] >
            5 * 1024 * 1024
        ) {

            $errors[] =
                "Product image must not exceed 5 MB.";
        }


        /* ---------------------------------------------
           CHECK MIME TYPE
        --------------------------------------------- */

        $allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        $fileType = mime_content_type(
            $_FILES["image"]["tmp_name"]
        );

        if (!in_array(
            $fileType,
            $allowedTypes,
            true
        )) {

            $errors[] =
                "Only JPG, PNG, and WEBP images are allowed.";
        }
    }
}


/* =====================================================
   STOP IF VALIDATION FAILED
===================================================== */

if (!empty($errors)) {

    $_SESSION["product_errors"] = $errors;

    header(
        "Location: ../admin/edit-product.php?product_id="
        . (int)$productId
    );

    exit;
}


/* =====================================================
   HANDLE NEW IMAGE
===================================================== */

if ($hasNewImage) {

    $uploadDirectory =
        "../images/products/";


    /* -------------------------------------------------
       CREATE DIRECTORY IF NEEDED
    ------------------------------------------------- */

    if (!is_dir($uploadDirectory)) {

        if (!mkdir(
            $uploadDirectory,
            0755,
            true
        )) {

            $_SESSION["product_errors"] = [
                "Unable to create image upload directory."
            ];

            header(
                "Location: ../admin/edit-product.php?product_id="
                . (int)$productId
            );

            exit;
        }
    }


    /* -------------------------------------------------
       GET MIME TYPE
    ------------------------------------------------- */

    $fileType = mime_content_type(
        $_FILES["image"]["tmp_name"]
    );


    /* -------------------------------------------------
       MIME TYPE TO EXTENSION
    ------------------------------------------------- */

    $extensionMap = [

        "image/jpeg" => "jpg",

        "image/png" => "png",

        "image/webp" => "webp"

    ];


    if (!isset($extensionMap[$fileType])) {

        $_SESSION["product_errors"] = [
            "Invalid image type."
        ];

        header(
            "Location: ../admin/edit-product.php?product_id="
            . (int)$productId
        );

        exit;
    }


    $extension =
        $extensionMap[$fileType];


    /* -------------------------------------------------
       CREATE UNIQUE FILE NAME
    ------------------------------------------------- */

    $fileName =
        "product_"
        . bin2hex(random_bytes(16))
        . "."
        . $extension;


    $destination =
        $uploadDirectory
        . $fileName;


    /* -------------------------------------------------
       MOVE UPLOADED FILE
    ------------------------------------------------- */

    if (!move_uploaded_file(
        $_FILES["image"]["tmp_name"],
        $destination
    )) {

        $_SESSION["product_errors"] = [
            "Unable to upload product image."
        ];

        header(
            "Location: ../admin/edit-product.php?product_id="
            . (int)$productId
        );

        exit;
    }


    /* -------------------------------------------------
       DATABASE IMAGE PATH
    ------------------------------------------------- */

    $newImagePath =
        "images/products/"
        . $fileName;
}


/* =====================================================
   DETERMINE IMAGE TO SAVE
===================================================== */

$imagePath =
    $newImagePath !== null
        ? $newImagePath
        : ($product["image"] ?? null);


/* =====================================================
   UPDATE PRODUCT
===================================================== */

try {

    $updated = updateProduct(
        $pdo,
        (int)$productId,
        $productName,
        $description,
        (float)$price,
        (int)$stock,
        $categoryId,
        $imagePath,
        $status
    );


    /* -------------------------------------------------
       CHECK UPDATE RESULT
    ------------------------------------------------- */

    if (!$updated) {

        throw new PDOException(
            "Product update failed."
        );
    }


    /* =================================================
       DELETE OLD IMAGE
    ================================================= */

    if (
        $newImagePath !== null &&
        !empty($product["image"])
    ) {

        $oldImage =
            "../"
            . ltrim(
                $product["image"],
                "/"
            );


        if (
            file_exists($oldImage) &&
            is_file($oldImage)
        ) {

            @unlink($oldImage);
        }
    }


    /* =================================================
       SUCCESS MESSAGE
    ================================================= */

    $_SESSION["product_success"] =
        "Product updated successfully.";


    /* =================================================
       RETURN TO PRODUCT MANAGEMENT
    ================================================= */

    header(
        "Location: ../admin/admin-products.php"
    );

    exit;


} catch (PDOException $e) {

    /* =================================================
       REMOVE NEW IMAGE IF DATABASE UPDATE FAILED
    ================================================= */

    if ($newImagePath !== null) {

        $uploadedFile =
            "../"
            . ltrim(
                $newImagePath,
                "/"
            );


        if (
            file_exists($uploadedFile) &&
            is_file($uploadedFile)
        ) {

            @unlink($uploadedFile);
        }
    }


    /* =================================================
       ERROR MESSAGE
    ================================================= */

    $_SESSION["product_errors"] = [
        "Unable to update product. Please try again."
    ];


    /* =================================================
       RETURN TO EDIT PAGE
    ================================================= */

    header(
        "Location: ../admin/edit-product.php?product_id="
        . (int)$productId
    );

    exit;
}