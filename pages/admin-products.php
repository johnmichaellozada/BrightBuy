<?php

session_start();
require_once "../db.php";


/* =====================================================
   ADMIN ACCESS
===================================================== */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}


/* =====================================================
   CHECK CURRENT USER ROLE FROM DATABASE
===================================================== */

$userStmt = $pdo->prepare("
    SELECT role
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$userStmt->execute([
    $_SESSION["user_id"]
]);

$currentUser = $userStmt->fetch();

if (
    !$currentUser ||
    $currentUser["role"] !== "admin"
) {
    die("Access denied. Administrator privileges required.");
}


/* =====================================================
   GET PRODUCTS
===================================================== */

$productStmt = $pdo->query("
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

$products = $productStmt->fetchAll();

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

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link
    rel="stylesheet"
    href="../css/styles.css"
    >
    
</head>

<body class="admin-inventory-page">


<!-- =====================================================
     ADMIN HEADER
===================================================== -->

<section class="admin-header">

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

                Orders

            </a>

        </div>

    </div>

</section>


<!-- =====================================================
     PRODUCT CONTENT
===================================================== -->

<div class="container py-5">

    <div class="card product-card">

        <div class="card-body p-4">

            <div
                class="d-flex justify-content-between
                align-items-center mb-4"
            >

                <div>

                    <h3 class="fw-bold mb-1">
                        Inventory
                    </h3>

                    <p class="text-muted mb-0">
                        Update the available stock for each product.
                    </p>

                </div>

                <span class="badge bg-primary fs-6">

                    <?= count($products) ?> Products

                </span>

            </div>


            <?php if (!$products): ?>

                <div class="text-center py-5">

                    <i
                        class="bi bi-box"
                        style="font-size: 60px;"
                    ></i>

                    <h4 class="mt-3">
                        No Products Found
                    </h4>

                </div>


            <?php else: ?>


                <?php foreach ($products as $product): ?>

                    <div class="product-row">

                        <div class="row align-items-center g-3">


                            <!-- PRODUCT IMAGE -->

                            <div class="col-md-1 text-center">

                                <img
                                    src="../<?= htmlspecialchars(
                                        $product["image"]
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $product["product_name"]
                                    ) ?>"
                                    class="product-image"
                                >

                            </div>


                            <!-- PRODUCT INFORMATION -->

                            <div class="col-md-4">

                                <h5 class="fw-bold mb-1">

                                    <?= htmlspecialchars(
                                        $product["product_name"]
                                    ) ?>

                                </h5>

                                <small class="text-muted">

                                    <?= htmlspecialchars(
                                        $product["category_name"] ??
                                        "Uncategorized"
                                    ) ?>

                                </small>

                                <div class="text-primary fw-bold mt-1">

                                    ₱<?= number_format(
                                        $product["price"],
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
                                        value="<?= (int)$product[
                                            "product_id"
                                        ] ?>"
                                    >

                                    <input
                                        type="number"
                                        name="stock"
                                        value="<?= (int)$product[
                                            "stock"
                                        ] ?>"
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

                            <div class="col-md-2">

                                <?php if (
                                    $product["status"] === "active"
                                ): ?>

                                    <span class="badge bg-success">

                                        Active

                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">

                                        <?= htmlspecialchars(
                                            $product["status"]
                                        ) ?>

                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>


            <?php endif; ?>

        </div>

    </div>


    <!-- NAVIGATION -->

    <div class="text-center mt-4">

    <a
        href="admin-dashboard.php"
        class="btn btn-outline-primary px-4"
    >

        <i class="bi bi-cart-check"></i>

        Order Management

    </a>

</div>

</div>

</body>

</html>