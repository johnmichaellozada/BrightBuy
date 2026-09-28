<?php

/**
 * BrightBuy Edit Product
 *
 * Displays the product editing form.
 * The actual database update is handled by:
 * actions/edit-product.php
 *
 * Database access is handled through reusable functions
 * in includes/product-functions.php.
 */

require_once "../db.php";
require_once "../includes/auth.php";
require_once "../includes/product-functions.php";


/* =====================================================
   ADMIN ACCESS
===================================================== */

requireAdmin($pdo, "admin-login.php");


/* =====================================================
   GET PRODUCT ID
===================================================== */

/*
 * Accept both:
 *
 * edit-product.php?product_id=1
 *
 * and:
 *
 * edit-product.php?id=1
 *
 * This prevents the product ID parameter
 * from causing an invalid product error.
 */

$productId = null;


/* FIRST: product_id */

if (isset($_GET["product_id"])) {

    $productId = filter_var(
        $_GET["product_id"],
        FILTER_VALIDATE_INT
    );

}


/* SECOND: fallback to id */

if (
    (!$productId || $productId <= 0)
    && isset($_GET["id"])
) {

    $productId = filter_var(
        $_GET["id"],
        FILTER_VALIDATE_INT
    );

}


/* VALIDATE */

if (!$productId || $productId <= 0) {

    $_SESSION["product_errors"] = [
        "Invalid product ID."
    ];

    header("Location: admin-products.php");
    exit;
}


/* =====================================================
   GET PRODUCT
===================================================== */

$product = getProductById($pdo, $productId);


if (!$product) {

    $_SESSION["product_errors"] = [
        "Product not found."
    ];

    header("Location: admin-products.php");
    exit;
}


/* =====================================================
   GET CATEGORIES
===================================================== */

$categories = getAllCategories($pdo);


/* =====================================================
   GET SESSION MESSAGES
===================================================== */

$errors = $_SESSION["product_errors"] ?? [];

unset($_SESSION["product_errors"]);


/* =====================================================
   HTML
===================================================== */

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>BrightBuy | Edit Product</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- BrightBuy CSS -->

    <link
        rel="stylesheet"
        href="../css/styles.css"
    >

</head>


<body class="admin-inventory-page">


<!-- =====================================================
     ADMIN HEADER
===================================================== -->

<header class="admin-header">

    <div class="container">

        <div
            class="d-flex justify-content-between
            align-items-center flex-wrap gap-3"
        >


            <!-- PAGE TITLE -->

            <div>

                <h2 class="mb-1">

                    <i class="bi bi-pencil-square"></i>

                    Edit Product

                </h2>

                <p class="mb-0">

                    Update the information for this product.

                </p>

            </div>


            <!-- BACK BUTTON -->

            <a
                href="admin-products.php"
                class="btn btn-light"
            >

                <i class="bi bi-arrow-left"></i>

                Back to Products

            </a>

        </div>

    </div>

</header>



<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="card product-card">

                <div class="card-body p-4 p-md-5">


                    <!-- =================================================
                         ERROR MESSAGES
                    ================================================== -->

                    <?php if (!empty($errors)): ?>

                        <div class="alert alert-danger">

                            <i class="bi bi-exclamation-circle-fill"></i>

                            <strong>
                                Please check the following:
                            </strong>

                            <ul class="mb-0 mt-2">

                                <?php foreach ($errors as $error): ?>

                                    <li>

                                        <?= htmlspecialchars(
                                            $error,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </li>

                                <?php endforeach; ?>

                            </ul>

                        </div>

                    <?php endif; ?>



                    <!-- =================================================
                         FORM HEADER
                    ================================================== -->

                    <div class="mb-4">

                        <h3 class="fw-bold mb-1">

                            Product Information

                        </h3>

                        <p class="text-muted mb-0">

                            Edit the details below and save your changes.

                        </p>

                    </div>



                    <!-- =================================================
                         EDIT PRODUCT FORM
                    ================================================== -->

                    <form
                        method="POST"
                        action="../actions/edit-product.php"
                        enctype="multipart/form-data"
                    >


                        <!-- =============================================
                             PRODUCT ID
                        ============================================== -->

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?= (int)$product["product_id"] ?>"
                        >



                        <!-- =============================================
                             PRODUCT NAME
                        ============================================== -->

                        <div class="mb-3">

                            <label
                                for="product_name"
                                class="form-label fw-semibold"
                            >

                                Product Name

                            </label>

                            <input
                                type="text"
                                id="product_name"
                                name="product_name"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $product["product_name"] ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                                maxlength="255"
                                required
                            >

                        </div>



                        <!-- =============================================
                             DESCRIPTION
                        ============================================== -->

                        <div class="mb-3">

                            <label
                                for="description"
                                class="form-label fw-semibold"
                            >

                                Description

                            </label>

                            <textarea
                                id="description"
                                name="description"
                                class="form-control"
                                rows="4"
                                maxlength="2000"
                            ><?= htmlspecialchars(
                                $product["description"] ?? "",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?></textarea>

                        </div>



                        <!-- =============================================
                             CATEGORY
                        ============================================== -->

                        <div class="mb-3">

                            <label
                                for="category_id"
                                class="form-label fw-semibold"
                            >

                                Category

                            </label>

                            <select
                                id="category_id"
                                name="category_id"
                                class="form-select"
                            >

                                <option value="">
                                    Select Category
                                </option>


                                <?php foreach ($categories as $category): ?>

                                    <?php
                                    $categoryId =
                                        (int)$category["category_id"];

                                    $selectedCategoryId =
                                        (int)($product["category_id"] ?? 0);
                                    ?>

                                    <option
                                        value="<?= $categoryId ?>"
                                        <?= $selectedCategoryId === $categoryId
                                            ? "selected"
                                            : "" ?>
                                    >

                                        <?= htmlspecialchars(
                                            $category["category_name"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>



                        <!-- =============================================
                             PRICE
                        ============================================== -->

                        <div class="mb-3">

                            <label
                                for="price"
                                class="form-label fw-semibold"
                            >

                                Price

                            </label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    ₱
                                </span>

                                <input
                                    type="number"
                                    id="price"
                                    name="price"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $product["price"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>"
                                    min="0"
                                    step="0.01"
                                    required
                                >

                            </div>

                        </div>



                        <!-- =============================================
                             STOCK
                        ============================================== -->

                        <div class="mb-3">

                            <label
                                for="stock"
                                class="form-label fw-semibold"
                            >

                                Stock

                            </label>

                            <input
                                type="number"
                                id="stock"
                                name="stock"
                                class="form-control"
                                value="<?= (int)$product["stock"] ?>"
                                min="0"
                                required
                            >

                        </div>



                        <!-- =============================================
                             CURRENT IMAGE
                        ============================================== -->

                        <div class="mb-3">

                            <label class="form-label fw-semibold">

                                Current Product Image

                            </label>


                            <?php if (!empty($product["image"])): ?>

                                <div class="mb-3">

                                    <img
                                        src="../<?= htmlspecialchars(
                                            $product["image"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $product["product_name"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                        class="product-image"
                                        style="
                                            max-width: 150px;
                                            max-height: 150px;
                                            object-fit: contain;
                                        "
                                    >

                                </div>

                            <?php else: ?>

                                <div class="text-muted">

                                    <i class="bi bi-image"></i>

                                    No image uploaded.

                                </div>

                            <?php endif; ?>

                        </div>



                        <!-- =============================================
                             REPLACE IMAGE
                        ============================================== -->

                        <div class="mb-3">

                            <label
                                for="image"
                                class="form-label fw-semibold"
                            >

                                Replace Product Image

                            </label>

                            <input
                                type="file"
                                id="image"
                                name="image"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            >

                            <div class="form-text">

                                Leave empty to keep the current image.

                                JPG, PNG, and WEBP only.

                            </div>

                        </div>



                        <!-- =============================================
                             STATUS
                        ============================================== -->

                        <div class="mb-4">

                            <label
                                for="status"
                                class="form-label fw-semibold"
                            >

                                Status

                            </label>

                            <?php
                            $currentStatus =
                                strtolower(
                                    trim(
                                        $product["status"] ?? "active"
                                    )
                                );
                            ?>

                            <select
                                id="status"
                                name="status"
                                class="form-select"
                                required
                            >

                                <option
                                    value="active"
                                    <?= $currentStatus === "active"
                                        ? "selected"
                                        : "" ?>
                                >

                                    Active

                                </option>

                                <option
                                    value="inactive"
                                    <?= $currentStatus === "inactive"
                                        ? "selected"
                                        : "" ?>
                                >

                                    Inactive

                                </option>

                            </select>

                        </div>



                        <!-- =============================================
                             FORM BUTTONS
                        ============================================== -->

                        <div class="d-flex gap-2 flex-wrap">

                            <button
                                type="submit"
                                class="btn btn-primary px-4"
                            >

                                <i class="bi bi-save"></i>

                                Save Changes

                            </button>


                            <a
                                href="admin-products.php"
                                class="btn btn-outline-secondary px-4"
                            >

                                <i class="bi bi-x-circle"></i>

                                Cancel

                            </a>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</main>



<!-- =====================================================
     BOOTSTRAP JS
===================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>