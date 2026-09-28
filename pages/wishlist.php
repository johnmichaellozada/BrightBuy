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
    header("Location: login.php");
    exit;
}

$user_id = (int)($_SESSION["user_id"] ?? 0);

if ($user_id <= 0) {
    header("Location: login.php");
    exit;
}


/* =====================================================
   GET USER WISHLIST
===================================================== */

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

$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>BrightBuy | My Wishlist</title>

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


<body>


<!-- =====================================================
     WISHLIST HEADER
===================================================== -->

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



<!-- =====================================================
     WISHLIST CONTENT
===================================================== -->

<div class="wishlist-page-container">

    <div class="container py-5">


        <?php if (empty($wishlist_items)): ?>

            <!-- =================================================
                 EMPTY WISHLIST
            ================================================= -->

            <div class="empty-wishlist text-center">

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


            <!-- =================================================
                 WISHLIST PRODUCTS
            ================================================= -->

            <div class="row g-4 wishlist-products-row">


                <?php foreach ($wishlist_items as $item): ?>


                    <?php

                    $wishlist_id =
                        (int)$item["wishlist_id"];

                    $product_id =
                        (int)$item["product_id"];

                    $product_name =
                        htmlspecialchars(
                            $item["product_name"],
                            ENT_QUOTES,
                            "UTF-8"
                        );

                    ?>


                    <div
                        class="col-md-6 col-lg-4 col-xl-3"
                        id="wishlist-item-<?= $wishlist_id ?>"
                    >


                        <div class="wishlist-card">


                            <!-- =================================================
                                 PRODUCT IMAGE
                            ================================================= -->

                            <?php if (!empty($item["image"])): ?>

                                <?php

                                $imagePath =
                                    "../" .
                                    ltrim(
                                        $item["image"],
                                        "/"
                                    );

                                ?>

                                <img
                                    src="<?= htmlspecialchars($imagePath) ?>"
                                    alt="<?= $product_name ?>"
                                    class="wishlist-image"
                                >

                            <?php else: ?>

                                <img
                                    src="../images/placeholder.png"
                                    alt="Product image"
                                    class="wishlist-image"
                                >

                            <?php endif; ?>



                            <!-- =================================================
                                 CARD BODY
                            ================================================= -->

                            <div class="wishlist-card-body">


                                <!-- PRODUCT NAME -->

                                <div class="product-name">

                                    <?= $product_name ?>

                                </div>



                                <!-- PRICE -->

                                <div class="product-price my-2">

                                    ₱<?= number_format(
                                        (float)$item["price"],
                                        2
                                    ) ?>

                                </div>



                                <!-- STOCK -->

                                <?php if ((int)$item["stock"] > 0): ?>

                                    <span class="badge bg-success mb-3">

                                        In Stock

                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary mb-3">

                                        Out of Stock

                                    </span>

                                <?php endif; ?>



                                <!-- =================================================
                                     BUTTONS
                                ================================================= -->

                                <div class="d-flex gap-2">


                                    <!-- VIEW PRODUCT -->

                                    <a
                                        href="shop.php"
                                        class="btn btn-primary flex-grow-1"
                                    >

                                        <i class="bi bi-eye"></i>

                                        View

                                    </a>



                                    <!-- =================================================
                                         REMOVE FROM WISHLIST

                                         IMPORTANT:
                                         This points to:

                                         ../actions/remove-wishlist.php

                                         NOT:

                                         ../actions/wishlist.php
                                    ================================================= -->

                                    <a
                                        href="../actions/remove-wishlist.php?id=<?= $product_id ?>"
                                        class="btn remove-btn"
                                        title="Remove from Wishlist"
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



        <!-- =====================================================
             BACK TO BRIGHTBUY
        ===================================================== -->

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