<?php

/**
 * BrightBuy Admin Product Management
 *
 * Handles the display of products for administrators.
 */

require_once "../db.php";
require_once "../includes/auth.php";
require_once "../includes/product-functions.php";

requireAdmin($pdo, "admin-login.php");


/* =====================================================
   GET PRODUCTS
===================================================== */

$products = getAllProducts($pdo);


/* =====================================================
   SESSION MESSAGES
===================================================== */

$success = $_SESSION["product_success"] ?? "";
$errors = $_SESSION["product_errors"] ?? [];

unset($_SESSION["product_success"]);
unset($_SESSION["product_errors"]);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>BrightBuy | Product Management</title>

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
     SUCCESS MESSAGE
===================================================== -->

<?php if ($success !== ""): ?>

    <div class="alert alert-success">

        <i class="bi bi-check-circle-fill"></i>

        <?= htmlspecialchars(
            $success,
            ENT_QUOTES,
            "UTF-8"
        ) ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     ERROR MESSAGES
===================================================== -->

<?php if (!empty($errors)): ?>

    <div class="alert alert-danger">

        <?php foreach ($errors as $error): ?>

            <div>

                <i class="bi bi-exclamation-circle-fill"></i>

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     ADMIN HEADER
===================================================== -->

<header class="admin-header">

    <div class="container">

        <div
            class="d-flex justify-content-between
            align-items-center flex-wrap gap-3"
        >

            <div>

                <h2 class="mb-1">

                    <i class="bi bi-box-seam"></i>

                    Product Management

                </h2>

                <p class="mb-0">

                    Manage BrightBuy products and stock.

                </p>

            </div>


            <a
                href="admin-dashboard.php"
                class="btn btn-light"
            >

                <i class="bi bi-arrow-left"></i>

                Dashboard

            </a>

        </div>

    </div>

</header>


<!-- =====================================================
     PRODUCT CONTENT
===================================================== -->

<main class="container py-5">

    <div class="card product-card">

        <div class="card-body p-4">


            <!-- =================================================
                 INVENTORY HEADER
            ================================================== -->

            <div
                class="d-flex justify-content-between
                align-items-center flex-wrap gap-3 mb-4"
            >

                <div>

                    <h3 class="fw-bold mb-1">

                        Inventory

                    </h3>

                    <p class="text-muted mb-0">

                        Manage BrightBuy products and stock.

                    </p>

                </div>


                <!-- ADD PRODUCT -->

                <a
                    href="add-product.php"
                    class="btn btn-primary"
                >

                    <i class="bi bi-plus-circle"></i>

                    Add Product

                </a>

            </div>


            <!-- =================================================
                 PRODUCT COUNT
            ================================================== -->

            <div class="mb-4">

                <span class="badge bg-primary fs-6">

                    <?= count($products) ?>

                    <?= count($products) === 1
                        ? "Product"
                        : "Products"
                    ?>

                </span>

            </div>


            <!-- =================================================
                 NO PRODUCTS
            ================================================== -->

            <?php if (empty($products)): ?>

                <div class="text-center py-5">

                    <i
                        class="bi bi-box"
                        style="font-size: 60px;"
                    ></i>

                    <h4 class="mt-3">

                        No Products Found

                    </h4>

                    <p class="text-muted">

                        Add your first product to the inventory.

                    </p>

                    <a
                        href="add-product.php"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-plus-circle"></i>

                        Add Product

                    </a>

                </div>


            <?php else: ?>


                <!-- =================================================
                     PRODUCT LIST
                ================================================== -->

                <?php foreach ($products as $product): ?>

                    <article class="product-row">

                        <div class="row align-items-center g-3">


                            <!-- PRODUCT IMAGE -->

                            <div class="col-md-1 text-center">

                                <?php if (!empty($product["image"])): ?>

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
                                    >

                                <?php else: ?>

                                    <div class="text-muted">

                                        <i
                                            class="bi bi-image"
                                            style="font-size: 35px;"
                                        ></i>

                                    </div>

                                <?php endif; ?>

                            </div>


                            <!-- PRODUCT INFORMATION -->

                            <div class="col-md-3">

                                <h5 class="fw-bold mb-1">

                                    <?= htmlspecialchars(
                                        $product["product_name"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </h5>

                                <small class="text-muted">

                                    <?= htmlspecialchars(
                                        $product["category_name"]
                                        ?? "Uncategorized",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </small>

                                <div class="text-primary fw-bold mt-1">

                                    ₱<?= number_format(
                                        (float)$product["price"],
                                        2
                                    ) ?>

                                </div>

                            </div>


                            <!-- CURRENT STOCK -->

                            <div class="col-md-2">

                                <small class="text-muted d-block">

                                    Current Stock

                                </small>

                                <span
                                    class="<?= (int)$product["stock"] <= 10
                                        ? "stock-low"
                                        : "stock-good"
                                    ?>"
                                >

                                    <?= (int)$product["stock"] ?>

                                    units

                                </span>

                            </div>


                            <!-- UPDATE STOCK -->

                            <div class="col-md-3">

                                <form
                                    method="POST"
                                    action="update-product-stock.php"
                                    class="d-flex gap-2"
                                >

                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?= (int)$product["product_id"] ?>"
                                    >

                                    <input
                                        type="number"
                                        name="stock"
                                        value="<?= (int)$product["stock"] ?>"
                                        min="0"
                                        class="form-control stock-input"
                                        required
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                    >

                                        <i class="bi bi-save"></i>

                                        Save

                                    </button>

                                </form>

                            </div>


                            <!-- STATUS -->

                            <div class="col-md-1">

                                <?php

                                $status = strtolower(
                                    trim($product["status"] ?? "")
                                );

                                ?>

                                <?php if ($status === "active"): ?>

                                    <span class="badge bg-success">

                                        Active

                                    </span>

                                <?php elseif ($status === "inactive"): ?>

                                    <span class="badge bg-secondary">

                                        Inactive

                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-warning text-dark">

                                        <?= htmlspecialchars(
                                            $product["status"] ?? "Unknown",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- ACTIONS -->

                            <div class="col-md-2">

                                <div class="d-flex gap-2 flex-wrap">


                                    <!-- EDIT -->

                                    <a
                                        href="edit-product.php?product_id=<?= (int)$product["product_id"] ?>"
                                        class="btn btn-outline-primary btn-sm"
                                    >

                                        <i class="bi bi-pencil"></i>

                                        Edit

                                    </a>


                                    <!-- DELETE -->

                                    <form
                                        method="POST"
                                        action="../actions/delete-product.php"
                                        onsubmit="return confirm(
                                            'Are you sure you want to delete this product?'
                                        );"
                                    >

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= (int)$product["product_id"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-outline-danger btn-sm"
                                        >

                                            <i class="bi bi-trash"></i>

                                            Delete

                                        </button>

                                    </form>

                                </div>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </div>


    <!-- =====================================================
         NAVIGATION
    ====================================================== -->

    <div class="text-center mt-4">

        <a
            href="admin-dashboard.php"
            class="btn btn-outline-primary px-4"
        >

            <i class="bi bi-cart-check"></i>

            Order Management

        </a>

    </div>

</main>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>