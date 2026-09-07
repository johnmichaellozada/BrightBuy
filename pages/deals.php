<?php

session_start();

require_once "../db.php";

/* =====================================================
   BRIGHTBUY - DEALS PAGE
===================================================== */

/* =====================================================
   WISHLIST COUNT
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
   DEAL PRODUCTS
===================================================== */

/*
   These are the original BrightBuy featured deals.

   The current database prices are already the selling
   prices. The old prices and discount percentages are
   displayed here for the Deals page.
*/

$dealInformation = [

    1 => [
        "old_price" => 699,
        "discount" => "-15%"
    ],

    2 => [
        "old_price" => 999,
        "discount" => "-20%"
    ],

    3 => [
        "old_price" => 1999,
        "discount" => "-15%"
    ],

    4 => [
        "old_price" => 1499,
        "discount" => "-16%"
    ],

    5 => [
        "old_price" => 469,
        "discount" => "-15%"
    ]

];


/* =====================================================
   GET DEAL PRODUCTS
===================================================== */

$dealIds = array_keys($dealInformation);

$placeholders = implode(
    ",",
    array_fill(
        0,
        count($dealIds),
        "?"
    )
);

$dealStmt = $pdo->prepare("
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
    ORDER BY p.product_id ASC
");

$dealStmt->execute($dealIds);

$deals = $dealStmt->fetchAll();


/* =====================================================
   PAGE TITLE
===================================================== */

$pageTitle = "Deals";


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
        content="BrightBuy Deals - Big Deals, Bigger Savings."
    >

    <title>
        BrightBuy | Deals
    </title>


    <!-- =================================================
         BOOTSTRAP
    ================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =================================================
         BOOTSTRAP ICONS
    ================================================== -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- =================================================
         POPPINS
    ================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- =================================================
         BRIGHTBUY CSS
    ================================================== -->

    <link
        rel="stylesheet"
        href="../css/styles.css"
    >

</head>


<body>


<!-- =========================================================
     BRIGHTBUY CANVAS
========================================================= -->

<div class="brightbuy-viewport">

    <div class="brightbuy-canvas">


<!-- =========================================================
     HEADER
========================================================= -->

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


                <!-- CATEGORY BUTTON -->

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


                <!-- HOME -->

                <li>

                    <a href="../index.php">
                        Home
                    </a>

                </li>


                <!-- SHOP -->

                <li>

                    <a href="shop.php">
                        Shop
                    </a>

                </li>


                <!-- DEALS -->

                <li>

                    <a
                        href="deals.php"
                        class="active"
                    >
                        Deals
                    </a>

                </li>


                <!-- NEW ARRIVALS -->

                <li>

                    <a href="new-arrivals.php">
                        New Arrivals
                    </a>

                </li>


                <!-- TRACK ORDER -->

                <li>

                    <a href="track-order.php">
                        Track Order
                    </a>

                </li>


                <!-- HELP CENTER -->

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
     DEALS HERO
===================================================== -->

<section class="deals-hero">

    <div class="deals-hero-content">

        <div class="deals-label">
            SPECIAL OFFERS
        </div>

        <h1>
            Big Deals,
            <span>Bigger Savings!</span>
        </h1>

        <p>
            Save more on selected BrightBuy products.
            Grab your favorites before the deals are gone!
        </p>

    </div>

</section>


<!-- =====================================================
     DEALS CONTENT
===================================================== -->

<main class="deals-container">


    <!-- HEADING -->

    <div class="deals-heading">

        <span>
            LIMITED-TIME OFFERS
        </span>

        <h2>
            Hot Deals You’ll Love
        </h2>

        <p>
            Great products. Great prices. Limited-time savings.
        </p>

    </div>


    <!-- =================================================
         DEAL PRODUCTS
    ================================================== -->

    <?php if (!empty($deals)): ?>

        <div class="deals-grid">


            <?php foreach ($deals as $product): ?>

                <?php

                    $productId =
                        (int)$product["product_id"];

                    $stock =
                        (int)$product["stock"];

                    $price =
                        (float)$product["price"];

                    $oldPrice =
                        (float)$dealInformation[
                            $productId
                        ]["old_price"];

                    $discount =
                        $dealInformation[
                            $productId
                        ]["discount"];

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


                <article class="deal-card">


                    <!-- PRODUCT IMAGE -->

                    <div class="deal-image">


                        <!-- DISCOUNT -->

                        <span class="deal-badge">
                            <?= htmlspecialchars($discount) ?>
                        </span>


                        <!-- STOCK BADGE -->

                        <?php if ($stock > 0): ?>

                            <span class="deal-stock-badge">

                                <i class="bi bi-check-circle-fill"></i>

                                In Stock

                            </span>

                        <?php else: ?>

                            <span
                                class="deal-stock-badge out"
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
                            class="deal-wishlist wishlist-button <?= $inWishlist ? "active" : "" ?>"
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


                    <!-- PRODUCT INFORMATION -->

                    <div class="deal-info">


                        <!-- CATEGORY -->

                        <?php if (
                            !empty($product["category_name"])
                        ): ?>

                            <div class="deal-category">

                                <?= htmlspecialchars(
                                    $product["category_name"]
                                ) ?>

                            </div>

                        <?php endif; ?>


                        <!-- NAME -->

                        <div class="deal-name">

                            <?= htmlspecialchars(
                                $product["product_name"]
                            ) ?>

                        </div>


                        <!-- RATING -->

                        <div class="deal-rating">

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

                        <div class="deal-price-row">

                            <div class="deal-price">

                                ₱<?= number_format(
                                    $price,
                                    2
                                ) ?>

                            </div>

                            <div class="deal-old-price">

                                ₱<?= number_format(
                                    $oldPrice,
                                    2
                                ) ?>

                            </div>

                            <div class="deal-discount">

                                <?= htmlspecialchars(
                                    $discount
                                ) ?>

                            </div>

                        </div>


                        <!-- STOCK -->

                        <?php if ($stock > 10): ?>

                            <div class="deal-stock available">

                                <i class="bi bi-check-circle-fill"></i>

                                <?= $stock ?>
                                items left

                            </div>

                        <?php elseif ($stock > 0): ?>

                            <div class="deal-stock low">

                                <i class="bi bi-exclamation-circle-fill"></i>

                                Only <?= $stock ?>
                                left

                            </div>

                        <?php else: ?>

                            <div class="deal-stock out">

                                <i class="bi bi-x-circle-fill"></i>

                                Out of Stock

                            </div>

                        <?php endif; ?>


                        <!-- ADD TO CART -->

                        <?php if ($stock > 0): ?>

                            <form
                                method="POST"
                                action="add-to-cart.php"
                                class="deal-cart-form"
                            >

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= $productId ?>"
                                >

                                <button
                                    type="submit"
                                    class="deal-cart-button"
                                >

                                    <i class="bi bi-cart-plus"></i>

                                    Add to Cart

                                </button>

                            </form>

                        <?php else: ?>

                            <button
                                type="button"
                                class="deal-cart-button"
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

        <div
            class="text-center py-5"
        >

            <i
                class="bi bi-tag"
                style="
                    font-size:50px;
                    color:#1765d8;
                "
            ></i>

            <h3
                class="mt-3"
                style="
                    font-weight:700;
                    color:#071d63;
                "
            >
                No deals available
            </h3>

            <p
                style="
                    color:#777;
                "
            >
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

                    <a
                        href="#"
                        aria-label="Facebook"
                    >
                        <i class="bi bi-facebook"></i>
                    </a>

                    <a
                        href="#"
                        aria-label="Instagram"
                    >
                        <i class="bi bi-instagram"></i>
                    </a>

                    <a
                        href="#"
                        aria-label="Twitter"
                    >
                        <i class="bi bi-twitter-x"></i>
                    </a>

                    <a
                        href="#"
                        aria-label="YouTube"
                    >
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


        <!-- =================================================
             FOOTER BOTTOM
        ================================================== -->

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


<!-- =====================================================
     BOOTSTRAP JS
===================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<!-- =====================================================
     BRIGHTBUY JS
===================================================== -->

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