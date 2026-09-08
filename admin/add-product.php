<?php

/**
 * BrightBuy Add Product
 *
 * Displays the product creation form.
 * Database insertion is handled by actions/add-product.php.
 */

require_once "../db.php";
require_once "../includes/auth.php";

requireAdmin($pdo, "admin-login.php");

$categories = [];

$categoryStmt = $pdo->query("
    SELECT category_id, category_name
    FROM categories
    ORDER BY category_name ASC
");

$categories = $categoryStmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>BrightBuy | Add Product</title>

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
     HEADER
===================================================== -->

<header class="admin-header">

    <div class="container">

        <div
            class="d-flex justify-content-between
            align-items-center flex-wrap gap-3"
        >

            <div>

                <h2 class="mb-1">

                    <i class="bi bi-plus-circle"></i>

                    Add Product

                </h2>

                <p class="mb-0">

                    Add a new product to BrightBuy inventory.

                </p>

            </div>


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
     PRODUCT FORM
===================================================== -->

<main class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="card product-card">

                <div class="card-body p-4 p-md-5">


                    <!-- FORM HEADER -->

                    <div class="mb-4">

                        <h3 class="fw-bold mb-1">

                            Product Information

                        </h3>

                        <p class="text-muted mb-0">

                            Enter the information for the new product.

                        </p>

                    </div>


                    <!-- =================================================
                         ADD PRODUCT FORM
                    ================================================== -->

                    <form
                        method="POST"
                        action="../actions/add-product.php"
                        enctype="multipart/form-data"
                    >


                        <!-- PRODUCT NAME -->

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
                                placeholder="Enter product name"
                                maxlength="255"
                                required
                            >

                        </div>


                        <!-- DESCRIPTION -->

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
                                placeholder="Enter product description"
                            ></textarea>

                        </div>


                        <!-- CATEGORY -->

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

                                    <option
                                        value="<?= (int)$category["category_id"] ?>"
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


                        <!-- PRICE -->

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
                                    placeholder="0.00"
                                    min="0"
                                    step="0.01"
                                    required
                                >

                            </div>

                        </div>


                        <!-- STOCK -->

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
                                placeholder="Enter available stock"
                                min="0"
                                required
                            >

                        </div>


                        <!-- IMAGE -->

                        <div class="mb-3">

                            <label
                                for="image"
                                class="form-label fw-semibold"
                            >

                                Product Image

                            </label>

                            <input
                                type="file"
                                id="image"
                                name="image"
                                class="form-control"
                                accept="image/jpeg,image/png,image/webp"
                            >

                            <div class="form-text">

                                Accepted formats: JPG, PNG, WEBP.

                            </div>

                        </div>


                        <!-- STATUS -->

                        <div class="mb-4">

                            <label
                                for="status"
                                class="form-label fw-semibold"
                            >

                                Status

                            </label>

                            <select
                                id="status"
                                name="status"
                                class="form-select"
                                required
                            >

                                <option value="active">

                                    Active

                                </option>

                                <option value="inactive">

                                    Inactive

                                </option>

                            </select>

                        </div>


                        <!-- BUTTONS -->

                        <div
                            class="d-flex gap-2 flex-wrap"
                        >

                            <button
                                type="submit"
                                class="btn btn-primary px-4"
                            >

                                <i class="bi bi-plus-circle"></i>

                                Add Product

                            </button>


                            <a
                                href="admin-products.php"
                                class="btn btn-outline-secondary px-4"
                            >

                                Cancel

                            </a>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</main>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>