<?php

declare(strict_types=1);

session_start();

require_once "../db.php";

/* =========================================================
   ADMIN ACCESS CHECK
========================================================= */

function requireAdmin(PDO $pdo): void
{
    if (
        !isset($_SESSION["logged_in"]) ||
        $_SESSION["logged_in"] !== true ||
        !isset($_SESSION["user_id"])
    ) {
        header("Location: admin-login.php");
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT role
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $_SESSION["user_id"]
    ]);

    $user = $stmt->fetch();

    if (!$user || $user["role"] !== "admin") {
        http_response_code(403);
        exit("Access denied. Administrator privileges required.");
    }
}


/* =========================================================
   GET PRODUCTS
========================================================= */

function getProducts(PDO $pdo): array
{
    $stmt = $pdo->prepare("
        SELECT
            p.product_id,
            p.product_name,
            p.description,
            p.price,
            p.stock,
            p.image,
            p.status,
            c.category_name
        FROM products p
        LEFT JOIN categories c
            ON p.category_id = c.category_id
        ORDER BY p.product_id ASC
    ");

    $stmt->execute();

    return $stmt->fetchAll();
}


/* =========================================================
   ESCAPE HTML OUTPUT
========================================================= */

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =========================================================
   INITIALIZE PAGE
========================================================= */

requireAdmin($pdo);

$products = getProducts($pdo);

$totalProducts = count($products);

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
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >


    <!-- BrightBuy CSS -->
    <link
        rel="stylesheet"
        href="../css/styles.css"
    >

</head>


<body class="admin-inventory-page">


<!-- =========================================================
     ADMIN HEADER
========================================================= -->

<header class="admin-header">

    <div class="container">

        <div class="
            d-flex
            justify-content-between
            align-items-center
            flex-wrap
            gap-3
        ">

            <!-- PAGE TITLE -->

            <div>

                <h2 class="mb-1">

                    <i class="bi bi-box-seam"></i>

                    Product Management

                </h2>

                <p class="mb-0">

                    Manage BrightBuy products and stock.

                </p>

            </div>


            <!-- HEADER ACTIONS -->

            <div class="d-flex gap-2">

                <a
                    href="add-product.php"
                    class="btn btn-warning fw-semibold"
                >

                    <i class="bi bi-plus-circle"></i>

                    Add Product

                </a>


                <a
                    href="admin-dashboard.php"
                    class="btn btn-light"
                >

                    <i class="bi bi-arrow-left"></i>

                    Dashboard

                </a>

            </div>

        </div>

    </div>

</header>


<!-- =========================================================
     PRODUCT CONTENT
========================================================= -->

<main class="container py-5">


    <!-- =====================================================
         INVENTORY CARD
    ====================================================== -->

    <div class="card product-card">

        <div class="card-body p-4">


            <!-- =================================================
                 INVENTORY HEADER
            ================================================== -->

            <div class="
                d-flex
                justify-content-between
                align-items-center
                flex-wrap
                gap-3
                mb-4
            ">

                <div>

                    <h3 class="fw-bold mb-1">

                        Inventory

                    </h3>

                    <p class="text-muted mb-0">

                        Manage products, prices, stock, and status.

                    </p>

                </div>


                <span class="badge bg-primary fs-6">

                    <?= $totalProducts ?>

                    <?= $totalProducts === 1
                        ? "Product"
                        : "Products"
                    ?>

                </span>

            </div>


            <!-- =================================================
                 SUCCESS MESSAGE
            ================================================== -->

            <?php if (isset($_GET["success"])): ?>

                <div
                    class="alert alert-success alert-dismissible fade show"
                    role="alert"
                >

                    <i class="bi bi-check-circle-fill"></i>

                    <?= e($_GET["success"]) ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 ERROR MESSAGE
            ================================================== -->

            <?php if (isset($_GET["error"])): ?>

                <div
                    class="alert alert-danger alert-dismissible fade show"
                    role="alert"
                >

                    <i class="bi bi-exclamation-circle-fill"></i>

                    <?= e($_GET["error"]) ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>


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


                            <!-- =====================================
                                 PRODUCT IMAGE
                            ====================================== -->

                            <div class="col-md-1 text-center">

                                <?php if (!empty($product["image"])): ?>

                                    <img
                                        src="../<?= e($product["image"]) ?>"
                                        alt="<?= e($product["product_name"]) ?>"
                                        class="product-image"
                                    >

                                <?php else: ?>

                                    <div
                                        class="product-image
                                        d-flex
                                        align-items-center
                                        justify-content-center"
                                    >

                                        <i class="bi bi-image"></i>

                                    </div>

                                <?php endif; ?>

                            </div>


                            <!-- =====================================
                                 PRODUCT INFORMATION
                            ====================================== -->

                            <div class="col-md-3">

                                <h5 class="fw-bold mb-1">

                                    <?= e($product["product_name"]) ?>

                                </h5>


                                <small class="text-muted">

                                    <?= e(
                                        $product["category_name"]
                                        ?? "Uncategorized"
                                    ) ?>

                                </small>


                                <div class="text-primary fw-bold mt-1">

                                    ₱<?= number_format(
                                        (float)$product["price"],
                                        2
                                    ) ?>

                                </div>

                            </div>


                            <!-- =====================================
                                 CURRENT STOCK
                            ====================================== -->

                            <div class="col-md-2">

                                <small class="text-muted d-block">

                                    Current Stock

                                </small>


                                <?php
                                $stock = (int)$product["stock"];

                                $stockClass =
                                    $stock <= 10
                                    ? "stock-low"
                                    : "stock-good";
                                ?>


                                <span class="<?= $stockClass ?>">

                                    <?= $stock ?>

                                    <?= $stock === 1
                                        ? "unit"
                                        : "units"
                                    ?>

                                </span>

                            </div>


                            <!-- =====================================
                                 UPDATE STOCK
                            ====================================== -->

                            <div class="col-md-2">

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
                                        value="<?= $stock ?>"
                                        min="0"
                                        class="form-control stock-input"
                                        required
                                    >


                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                        title="Update stock"
                                    >

                                        <i class="bi bi-save"></i>

                                    </button>

                                </form>

                            </div>


                            <!-- =====================================
                                 STATUS
                            ====================================== -->

                            <div class="col-md-1">

                                <?php if ($product["status"] === "active"): ?>

                                    <span class="badge bg-success">

                                        Active

                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">

                                        <?= e($product["status"]) ?>

                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- =====================================
                                 CRUD ACTIONS
                            ====================================== -->

                            <div class="col-md-3">

                                <div
                                    class="
                                    d-flex
                                    justify-content-md-end
                                    gap-2
                                    flex-wrap
                                    "
                                >

                                    <!-- EDIT -->

                                    <a
                                        href="edit-product.php?id=<?= (int)$product["product_id"] ?>"
                                        class="btn btn-outline-primary btn-sm"
                                    >

                                        <i class="bi bi-pencil-square"></i>

                                        Edit

                                    </a>


                                    <!-- DELETE -->

                                    <form
                                        method="POST"
                                        action="delete-product.php"
                                        onsubmit="
                                            return confirm(
                                                'Are you sure you want to delete this product?'
                                            );
                                        "
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


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>