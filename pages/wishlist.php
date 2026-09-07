<?php

session_start();
require_once "../db.php";

// User must be logged in
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// Get this user's wishlist products
$stmt = $pdo->prepare("
    SELECT
        w.wishlist_id,
        p.product_id,
        p.product_name,
        p.description,
        p.price,
        p.stock,
        p.image,
        p.status
    FROM wishlist w
    INNER JOIN products p
        ON w.product_id = p.product_id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
");

$stmt->execute([$user_id]);

$wishlist_items = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BrightBuy | My Wishlist</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link
    rel="stylesheet"
    href="../css/styles.css"
>

</head>

<body>

    <!-- Header -->
    <section class="wishlist-header">

        <div class="container text-center">

            <i class="bi bi-heart-fill fs-1 text-warning"></i>

            <h2 class="mt-2 mb-1">
                My Wishlist
            </h2>

            <p class="mb-0">
                Your favorite BrightBuy products
            </p>

        </div>

    </section>


    <!-- Wishlist -->
    <div class="wishlist-page-container">

    <div class="container py-5">

        <?php if (empty($wishlist_items)): ?>

            <div class="empty-wishlist">

                <i class="bi bi-heart"></i>

                <h3 class="mt-3">
                    Your Wishlist is Empty
                </h3>

                <p class="text-muted">
                    You haven't added any products to your wishlist yet.
                </p>

                <a
                    href="shop.php"
                    class="btn btn-primary px-4"
                >
                    <i class="bi bi-bag"></i>
                    Start Shopping
                </a>

            </div>

        <?php else: ?>

            <div class="row g-4 wishlist-products-row">

                <?php foreach ($wishlist_items as $item): ?>

                    <div class="col-md-6 col-lg-4 col-xl-3">

                        <div class="wishlist-card">

                            <?php if (!empty($item["image"])): ?>

                                <img
                                    src="../<?= htmlspecialchars($item["image"]) ?>"
                                    alt="<?= htmlspecialchars($item["product_name"]) ?>"
                                    class="wishlist-image"
                                >

                            <?php else: ?>

                                <img
                                    src="../images/placeholder.png"
                                    alt="Product image"
                                    class="wishlist-image"
                                >

                            <?php endif; ?>


                            <div class="wishlist-card-body">

                                <div class="product-name">

                                    <?= htmlspecialchars($item["product_name"]) ?>

                                </div>


                                <div class="product-price my-2">

                                    ₱<?= number_format($item["price"], 2) ?>

                                </div>


                                <?php if ((int)$item["stock"] > 0): ?>

                                    <span class="badge bg-success mb-3">
                                        In Stock
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary mb-3">
                                        Out of Stock
                                    </span>

                                <?php endif; ?>


                                <div class="d-flex gap-2">

                                    <a
                                        href="shop.php"
                                        class="btn btn-primary flex-grow-1"
                                    >
                                        <i class="bi bi-eye"></i>
                                        View
                                    </a>


                                    <a
                                        href="remove-wishlist.php?id=<?= (int)$item["wishlist_id"] ?>"
                                        class="btn remove-btn"
                                        onclick="return confirm('Remove this product from your wishlist?');"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <div class="text-center mt-5">

            <a
    href="../index.php"
    class="btn wishlist-back-btn"
>
                <i class="bi bi-house"></i>
                Back to BrightBuy
            </a>

        </div>

    </div>
                                </div>                                   
</body>

</html>