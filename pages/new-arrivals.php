<?php

session_start();

require_once "../db.php";


/* =====================================================
   WISHLIST
===================================================== */

$wishlistCount = 0;
$wishlistProductIds = [];

if (
    isset($_SESSION["logged_in"]) &&
    $_SESSION["logged_in"] === true
) {

    $user_id = (int)$_SESSION["user_id"];

    $wishlistStmt = $pdo->prepare("
        SELECT product_id
        FROM wishlist
        WHERE user_id = ?
    ");

    $wishlistStmt->execute([$user_id]);

    $wishlistProductIds = $wishlistStmt->fetchAll(
        PDO::FETCH_COLUMN
    );

    $wishlistCount = count($wishlistProductIds);
}


/* =====================================================
   CART COUNT
===================================================== */

$cartCount = 0;

if (
    isset($_SESSION["logged_in"]) &&
    $_SESSION["logged_in"] === true
) {

    $user_id = (int)$_SESSION["user_id"];

    $cartStmt = $pdo->prepare("
        SELECT COALESCE(SUM(ci.quantity), 0)
        FROM cart_items ci
        INNER JOIN cart c
            ON ci.cart_id = c.cart_id
        WHERE c.user_id = ?
    ");

    $cartStmt->execute([$user_id]);

    $cartCount = (int)$cartStmt->fetchColumn();
}


/* =====================================================
   NEW ARRIVAL PRODUCT IDS
   3 PRODUCTS PER CATEGORY
===================================================== */

$newArrivalIds = [

    // Electronics
    6, 7, 8,

    // Fashion
    9, 10, 11,

    // Home & Living
    12, 13, 14,

    // Toys & Games
    16, 17, 18,

    // Beauty & Health
    21, 22, 23,

    // Sports & Outdoors
    26, 27, 28

];


$placeholders = implode(
    ",",
    array_fill(
        0,
        count($newArrivalIds),
        "?"
    )
);


/* =====================================================
   GET NEW ARRIVALS
===================================================== */

$productStmt = $pdo->prepare("
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

    WHERE p.product_id IN ($placeholders)
      AND p.status = 'Active'

    ORDER BY
        p.product_id ASC
");

$productStmt->execute($newArrivalIds);

$newArrivals = $productStmt->fetchAll();


?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Discover the latest products at BrightBuy."
    >

    <title>
        BrightBuy | New Arrivals
    </title>


    <!-- BOOTSTRAP -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- BOOTSTRAP ICONS -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- POPPINS -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- BRIGHTBUY CSS -->

    <link
        rel="stylesheet"
        href="../css/styles.css"
    >

</head>


<body>


<!-- =====================================================
     BRIGHTBUY VIEWPORT
===================================================== -->

<div class="brightbuy-viewport">

    <div class="brightbuy-canvas">


<!-- =====================================================
     HEADER
===================================================== -->

<header class="site-header">

    <div class="header-main">

        <div class="container-fluid bright-container">

            <div class="header-row">


                <!-- LOGO -->

                <a
                    href="../index.php"
                    class="brand-logo"
                >

                    <img
                        src="../images/logo.png"
                        alt="BrightBuy Logo"
                    >

                </a>


                <!-- ALL CATEGORIES -->

                <a
                    href="shop.php"
                    class="category-button"
                >

                    <i class="bi bi-grid-fill"></i>

                    <span>
                        All Categories
                    </span>

                </a>


                <!-- SEARCH -->

                <form
                    class="search-box"
                    action="shop.php"
                    method="GET"
                >

                    <input
                        type="text"
                        name="search"
                        placeholder="Search for products..."
                        aria-label="Search for products"
                    >

                    <button type="submit">

                        <i class="bi bi-search"></i>

                    </button>

                </form>


                <!-- HEADER ACTIONS -->

                <div class="header-actions">


                    <?php if (
                        isset($_SESSION["logged_in"]) &&
                        $_SESSION["logged_in"] === true
                    ): ?>

                        <div
                            class="d-flex align-items-center gap-2"
                        >

                            <a
                                href="account.php"
                                class="header-action"
                            >

                                <i class="bi bi-person"></i>

                                <span>
                                    <?= htmlspecialchars(
                                        $_SESSION["first_name"]
                                    ) ?>
                                </span>

                            </a>


                            <a
                                href="logout.php"
                                class="header-action"
                            >

                                <i class="bi bi-box-arrow-right"></i>

                                <span>
                                    Logout
                                </span>

                            </a>

                        </div>

                    <?php else: ?>

                        <a
                            href="login.php"
                            class="header-action"
                        >

                            <i class="bi bi-person"></i>

                            <span>
                                Account
                            </span>

                        </a>

                    <?php endif; ?>


                    <!-- WISHLIST -->

                    <a
                        href="wishlist.php"
                        class="header-action wishlist-action"
                    >

                        <i class="bi bi-heart"></i>

                        <span>
                            Wishlist
                        </span>

                        <?php if ($wishlistCount > 0): ?>

                            <b class="wishlist-count">
                                <?= $wishlistCount ?>
                            </b>

                        <?php endif; ?>

                    </a>


                    <!-- CART -->

                    <a
                        href="cart.php"
                        class="header-action cart-action"
                    >

                        <i class="bi bi-cart3"></i>

                        <span>
                            Cart
                        </span>

                        <?php if ($cartCount > 0): ?>

                            <b class="cart-count">
                                <?= $cartCount ?>
                            </b>

                        <?php endif; ?>

                    </a>

                </div>

            </div>

        </div>

    </div>


    <!-- =================================================
         NAVIGATION
    ================================================== -->

    <nav class="main-navigation">

        <div class="container-fluid bright-container">

            <ul class="nav-menu">


                <li>

                    <a href="../index.php">
                        Home
                    </a>

                </li>


                <li>

                    <a href="shop.php">
                        Shop
                    </a>

                </li>


                <li>

                    <a href="deals.php">
                        Deals
                    </a>

                </li>


                <li>

                    <a
                        href="new-arrivals.php"
                        class="active"
                    >
                        New Arrivals
                    </a>

                </li>


                <li>

                    <a href="track-order.php">
                        Track Order
                    </a>

                </li>


                <li>

                    <a href="help.php">
                        Help Center
                    </a>

                </li>


            </ul>

        </div>

    </nav>

</header>


<!-- =====================================================
     NEW ARRIVALS HERO
===================================================== -->

<section class="new-arrivals-hero">

    <div class="new-arrivals-hero-content">

        <span class="new-arrivals-label">
            JUST ARRIVED
        </span>

        <h1>
            New Arrivals
        </h1>

        <p>
            Discover the newest products added to BrightBuy.
            Fresh finds, great quality, and prices you'll love.
        </p>

    </div>

</section>


<!-- =====================================================
     NEW ARRIVALS CONTENT
===================================================== -->

<main class="new-arrivals-container">


    <div class="new-arrivals-heading">

        <span>
            FRESH FROM BRIGHTBUY
        </span>

        <h2>
            Explore Our New Arrivals
        </h2>

        <p>
            Check out our latest products across every category.
        </p>

    </div>


    <?php if (!empty($newArrivals)): ?>


        <div class="new-arrivals-grid">


            <?php foreach ($newArrivals as $product): ?>

                <?php

                    $productId =
                        (int)$product["product_id"];

                    $stock =
                        (int)$product["stock"];

                    $image =
                        !empty($product["image"])
                            ? "../" . ltrim(
                                $product["image"],
                                "/"
                            )
                            : "../images/placeholder.png";

                    $inWishlist =
                        in_array(
                            $productId,
                            array_map(
                                "intval",
                                $wishlistProductIds
                            ),
                            true
                        );

                ?>


                <article class="new-arrival-card">


                    <!-- PRODUCT IMAGE -->

                    <div class="new-arrival-image">


                        <!-- NEW BADGE -->

                        <span class="new-arrival-badge">
                            NEW
                        </span>


                        <!-- STOCK -->

                        <?php if ($stock > 0): ?>

                            <span
                                class="new-arrival-stock-badge"
                            >

                                <i class="bi bi-check-circle-fill"></i>

                                In Stock

                            </span>

                        <?php else: ?>

                            <span
                                class="new-arrival-stock-badge out"
                            >

                                <i class="bi bi-x-circle-fill"></i>

                                Sold Out

                            </span>

                        <?php endif; ?>


                        <!-- IMAGE -->

                        <img
                            src="<?= htmlspecialchars($image) ?>"
                            alt="<?= htmlspecialchars(
                                $product["product_name"]
                            ) ?>"
                        >


                        <!-- WISHLIST -->

                        <button
                            type="button"
                            class="new-arrival-wishlist wishlist-button <?= $inWishlist ? "active" : "" ?>"
                            data-product-id="<?= $productId ?>"
                            aria-label="Add to wishlist"
                        >

                            <i
                                class="bi <?= $inWishlist
                                    ? "bi-heart-fill"
                                    : "bi-heart"
                                ?>"
                            ></i>

                        </button>

                    </div>


                    <!-- PRODUCT INFO -->

                    <div class="new-arrival-info">


                        <!-- CATEGORY -->

                        <div class="new-arrival-category">

                            <?= htmlspecialchars(
                                $product["category_name"] ?? ""
                            ) ?>

                        </div>


                        <!-- NAME -->

                        <div class="new-arrival-name">

                            <?= htmlspecialchars(
                                $product["product_name"]
                            ) ?>

                        </div>


                        <!-- RATING -->

                        <div class="new-arrival-rating">

                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-half"></i>

                            <span>
                                4.8
                            </span>

                        </div>


                        <!-- PRICE -->

                        <div class="new-arrival-price">

                            ₱<?= number_format(
                                (float)$product["price"],
                                2
                            ) ?>

                        </div>


                        <!-- STOCK TEXT -->

                        <?php if ($stock > 10): ?>

                            <div
                                class="new-arrival-stock available"
                            >

                                <i class="bi bi-check-circle-fill"></i>

                                <?= $stock ?>
                                items left

                            </div>

                        <?php elseif ($stock > 0): ?>

                            <div
                                class="new-arrival-stock low"
                            >

                                <i class="bi bi-exclamation-circle-fill"></i>

                                Only <?= $stock ?>
                                left

                            </div>

                        <?php else: ?>

                            <div
                                class="new-arrival-stock out"
                            >

                                <i class="bi bi-x-circle-fill"></i>

                                Out of Stock

                            </div>

                        <?php endif; ?>


                        <!-- ADD TO CART -->

                        <?php if ($stock > 0): ?>

                            <form
                                method="POST"
                                action="add-to-cart.php"
                                class="new-arrival-cart-form"
                            >

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= $productId ?>"
                                >

                                <button
                                    type="submit"
                                    class="new-arrival-cart-button"
                                >

                                    <i class="bi bi-cart-plus"></i>

                                    Add to Cart

                                </button>

                            </form>

                        <?php else: ?>

                            <button
                                type="button"
                                class="new-arrival-cart-button"
                                disabled
                            >

                                <i class="bi bi-x-circle"></i>

                                Out of Stock

                            </button>

                        <?php endif; ?>


                    </div>

                </article>


            <?php endforeach; ?>


        </div>


    <?php else: ?>


        <div class="new-arrivals-empty">

            <i class="bi bi-box-seam"></i>

            <h3>
                No new arrivals available
            </h3>

            <p>
                Please check back again soon.
            </p>

            <a
                href="shop.php"
                class="btn btn-primary"
            >
                Browse Products
            </a>

        </div>


    <?php endif; ?>


</main>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="site-footer">

    <div class="container-fluid bright-container">

        <div class="footer-main">


            <!-- BRAND -->

            <div class="footer-brand">

                <a href="../index.php">

                    <img
                        src="../images/logo.png"
                        alt="BrightBuy"
                    >

                </a>

                <p>
                    Smart Shopping,<br>
                    Brighter Living.
                </p>

                <div class="social-links">

                    <a href="#">
                        <i class="bi bi-facebook"></i>
                    </a>

                    <a href="#">
                        <i class="bi bi-instagram"></i>
                    </a>

                    <a href="#">
                        <i class="bi bi-twitter-x"></i>
                    </a>

                    <a href="#">
                        <i class="bi bi-youtube"></i>
                    </a>

                </div>

            </div>


            <!-- SHOP -->

            <div class="footer-column">

                <h3>
                    Shop
                </h3>

                <a href="shop.php">
                    All Categories
                </a>

                <a href="deals.php">
                    Deals
                </a>

                <a href="new-arrivals.php">
                    New Arrivals
                </a>

                <a href="shop.php">
                    Best Sellers
                </a>

            </div>


            <!-- CUSTOMER SERVICES -->

            <div class="footer-column">

                <h3>
                    Customer Services
                </h3>

                <a href="help.php">
                    Help Center
                </a>

                <a href="track-order.php">
                    Track Order
                </a>

                <a href="#">
                    Returns & Refunds
                </a>

                <a href="#">
                    Shipping Info
                </a>

            </div>


            <!-- ABOUT -->

            <div class="footer-column">

                <h3>
                    About us
                </h3>

                <a href="#">
                    About BrightBuy
                </a>

                <a href="#">
                    Careers
                </a>

                <a href="#">
                    Press & Media
                </a>

                <a href="#">
                    Contact Us
                </a>

            </div>


            <!-- ACCOUNT -->

            <div class="footer-column">

                <h3>
                    My Account
                </h3>

                <a href="my-orders.php">
                    My Orders
                </a>

                <a href="wishlist.php">
                    Wishlist
                </a>

                <a href="account.php">
                    Account Settings
                </a>

            </div>


            <!-- APP -->

            <div class="footer-column app-column">

                <h3>
                    Download Our App
                </h3>

                <p>
                    Get the app for better
                    shopping experience.
                </p>

                <div class="app-buttons">

                    <a
                        href="#"
                        class="app-button"
                    >

                        <i class="bi bi-apple"></i>

                        <span>
                            <small>
                                Download on the
                            </small>
                            App Store
                        </span>

                    </a>


                    <a
                        href="#"
                        class="app-button"
                    >

                        <i class="bi bi-google-play"></i>

                        <span>
                            <small>
                                GET IT ON
                            </small>
                            Google Play
                        </span>

                    </a>

                </div>

            </div>

        </div>


        <!-- FOOTER BOTTOM -->

        <div class="footer-bottom">

            <span>
                © 2026 BrightBuy. All rights reserved
            </span>

            <div class="footer-policies">

                <a href="#">
                    Privacy Policy
                </a>

                <a href="#">
                    Terms of Service
                </a>

                <a href="#">
                    Refund Policy
                </a>

            </div>

            <div class="payment-methods">

                <span>
                    VISA
                </span>

                <span>
                    ●●
                </span>

                <span>
                    PayPal
                </span>

                <span>
                    GPay
                </span>

            </div>

        </div>

    </div>

</footer>


    </div>

</div>


<!-- =====================================================
     BACK TO TOP
===================================================== -->

<button
    type="button"
    id="backToTop"
    aria-label="Back to top"
>

    <i class="bi bi-arrow-up"></i>

</button>


<!-- BOOTSTRAP JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<!-- BRIGHTBUY JS -->

<script src="../js/script.js"></script>


<!-- =====================================================
     WISHLIST
===================================================== -->

<script>

document
    .querySelectorAll(".wishlist-button")
    .forEach(button => {

        button.addEventListener(
            "click",
            function () {

                const productId =
                    this.dataset.productId;

                fetch(
                    "toggle-wishlist.php",
                    {
                        method: "POST",

                        headers: {
                            "Content-Type":
                                "application/x-www-form-urlencoded"
                        },

                        body:
                            "product_id=" +
                            encodeURIComponent(
                                productId
                            )
                    }
                )

                .then(response =>
                    response.json()
                )

                .then(data => {

                    if (
                        data.logged_in === false
                    ) {

                        window.location.href =
                            "login.php";

                        return;

                    }


                    if (data.success) {

                        const icon =
                            this.querySelector("i");


                        if (
                            data.in_wishlist
                        ) {

                            this.classList.add(
                                "active"
                            );

                            icon.className =
                                "bi bi-heart-fill";

                        } else {

                            this.classList.remove(
                                "active"
                            );

                            icon.className =
                                "bi bi-heart";

                        }

                    }

                })

                .catch(error => {

                    console.error(
                        "Wishlist error:",
                        error
                    );

                });

            }
        );

    });

</script>


</body>

</html>