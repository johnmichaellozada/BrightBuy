<?php

session_start();
require_once "db.php";

// =========================================================
// BRIGHTBUY HOMEPAGE
// =========================================================
// Handles:
// - User session
// - Wishlist count
// - Cart count
// - Product information
// - Live product stock
// - Categories
// - Homepage testimonials
// =========================================================


// =========================================================
// WISHLIST COUNT
// =========================================================

$wishlistProductIds = [];

if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {

    // Get all products currently saved in the user's wishlist.
    $wishlistStmt = $pdo->prepare("
        SELECT product_id
        FROM wishlist
        WHERE user_id = ?
    ");

    $wishlistStmt->execute([
        $_SESSION["user_id"]
    ]);

    // Store the product IDs as an array.
    $wishlistProductIds = $wishlistStmt->fetchAll(PDO::FETCH_COLUMN);
}

// Count the number of wishlist items.
$wishlistCount = count($wishlistProductIds);


// =========================================================
// CART COUNT
// =========================================================

$cartCount = 0;

if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {

    // Calculate the total quantity of products in the user's cart.
    $cartCountStmt = $pdo->prepare("
        SELECT COALESCE(SUM(ci.quantity), 0)
        FROM cart c
        INNER JOIN cart_items ci
            ON c.cart_id = ci.cart_id
        WHERE c.user_id = ?
    ");

    $cartCountStmt->execute([
        $_SESSION["user_id"]
    ]);

    $cartCount = (int) $cartCountStmt->fetchColumn();
}


// =========================================================
// PRODUCT CATEGORIES
// =========================================================

$categories = [

    [
        "name" => "Electronics",
        "items" => "120+ Items",
        "image" => "images/categories/electronics.png",
        "link" => "pages/shop.php?category=electronics"
    ],

    [
        "name" => "Fashion",
        "items" => "150+ Items",
        "image" => "images/categories/fashion.png",
        "link" => "pages/shop.php?category=fashion"
    ],

    [
        "name" => "Home & Living",
        "items" => "100+ Items",
        "image" => "images/categories/home.png",
        "link" => "pages/shop.php?category=home"
    ],

    [
        "name" => "Beauty & Health",
        "items" => "70+ Items",
        "image" => "images/categories/beauty.png",
        "link" => "pages/shop.php?category=beauty"
    ],

    [
        "name" => "Sports & Outdoors",
        "items" => "90+ Items",
        "image" => "images/categories/sports.png",
        "link" => "pages/shop.php?category=sports"
    ],

    [
        "name" => "Toys & Games",
        "items" => "80+ Items",
        "image" => "images/categories/toys.png",
        "link" => "pages/shop.php?category=toys"
    ]

];


// =========================================================
// FEATURED PRODUCT INFORMATION
// =========================================================

$products = [

    [
        "product_id" => 1,
        "name" => "Urban Backpack",
        "price" => "₱599",
        "old_price" => "₱699",
        "discount" => "-15%",
        "rating" => "4.9",
        "reviews" => "128",
        "image" => "images/products/product1.png"
    ],

    [
        "product_id" => 2,
        "name" => "Wireless Earbuds Pro 2",
        "price" => "₱799",
        "old_price" => "₱999",
        "discount" => "-20%",
        "rating" => "4.8",
        "reviews" => "215",
        "image" => "images/products/product2.png"
    ],

    [
        "product_id" => 3,
        "name" => "Classic White Sneakers",
        "price" => "₱1,699",
        "old_price" => "₱1,999",
        "discount" => "-15%",
        "rating" => "4.9",
        "reviews" => "186",
        "image" => "images/products/product3.png"
    ],

    [
        "product_id" => 4,
        "name" => "Smart Watch Series 5",
        "price" => "₱1,299",
        "old_price" => "₱1,499",
        "discount" => "-16%",
        "rating" => "4.8",
        "reviews" => "176",
        "image" => "images/products/product4.png"
    ],

    [
        "product_id" => 5,
        "name" => "Insulated Water Bottle 750ml",
        "price" => "₱399",
        "old_price" => "₱469",
        "discount" => "-15%",
        "rating" => "4.9",
        "reviews" => "243",
        "image" => "images/products/product5.png"
    ]

];


// =========================================================
// GET LIVE PRODUCT STOCK
// =========================================================
// Stock values are retrieved directly from the products table
// so that the homepage reflects the current database stock.
// =========================================================

$stockStmt = $pdo->query("
    SELECT product_id, stock
    FROM products
");

$productStocks = [];

while ($stockRow = $stockStmt->fetch()) {

    $productStocks[(int) $stockRow["product_id"]]
        = (int) $stockRow["stock"];
}


// =========================================================
// ATTACH LIVE STOCK TO FEATURED PRODUCTS
// =========================================================

foreach ($products as &$product) {

    $productId = (int) $product["product_id"];

    // Use 0 when the product does not have a stock record.
    $product["stock"] = $productStocks[$productId] ?? 0;
}

// Remove the reference created by foreach.
unset($product);


// =========================================================
// CUSTOMER TESTIMONIALS
// =========================================================

$testimonials = [

    [
        "text" =>
            "BrightBuy has amazing products at the best prices. Delivery is fast and customer service is excellent!",
        "name" => "John D.",
        "role" => "Verified Buyer"
    ],

    [
        "text" =>
            "I love how easy it is to shop here. Everything I need in one place with great deals every day!",
        "name" => "Maria S.",
        "role" => "Verified Buyer"
    ],

    [
        "text" =>
            "Quality products, secure payments, and fast delivery. Highly recommended!",
        "name" => "Mark T.",
        "role" => "Verified Buyer"
    ]

];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <!-- =====================================================
         BASIC PAGE INFORMATION
    ====================================================== -->

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="BrightBuy - Smart Shopping, Brighter Living."
    >

    <title>BrightBuy | Smart Shopping, Brighter Living</title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         BOOTSTRAP ICONS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- =====================================================
         GOOGLE FONT
    ====================================================== -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         BRIGHTBUY CUSTOM CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/styles.css"
    >

</head>


<body>

<!-- =========================================================
     MAIN BRIGHTBUY PAGE CONTAINER
========================================================= -->

<div class="brightbuy-viewport">

    <div class="brightbuy-canvas">


        <!-- =====================================================
             HEADER
        ====================================================== -->

        <header class="site-header">

            <!-- Main Header -->
            <div class="header-main">

                <div class="container-fluid bright-container">

                    <div class="header-row">


                        <!-- LOGO -->

                        <a
                            href="index.php"
                            class="brand-logo"
                        >

                            <img
                                src="images/logo.png"
                                alt="BrightBuy Logo"
                            >

                        </a>


                        <!-- ALL CATEGORIES BUTTON -->

                        <a
                            href="pages/shop.php"
                            class="category-button"
                        >

                            <i class="bi bi-grid-fill"></i>

                            <span>All Categories</span>

                        </a>


                        <!-- SEARCH BAR -->

                        <form
                            class="search-box"
                            action="pages/shop.php"
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

                                <!-- Logged-in user actions -->

                                <div class="d-flex align-items-center gap-2">

                                    <a
                                        href="pages/account.php"
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
                                        href="pages/logout.php"
                                        class="header-action"
                                    >

                                        <i class="bi bi-box-arrow-right"></i>

                                        <span>Logout</span>

                                    </a>

                                </div>

                            <?php else: ?>

                                <!-- Guest user -->

                                <a
                                    href="pages/login.php"
                                    class="header-action"
                                >

                                    <i class="bi bi-person"></i>

                                    <span>Account</span>

                                </a>

                            <?php endif; ?>


                            <!-- WISHLIST -->

                            <a
                                href="pages/wishlist.php"
                                class="header-action wishlist-action"
                            >

                                <i class="bi bi-heart"></i>

                                <span>Wishlist</span>

                                <?php if ($wishlistCount > 0): ?>

                                    <b class="wishlist-count">
                                        <?= $wishlistCount ?>
                                    </b>

                                <?php endif; ?>

                            </a>


                            <!-- CART -->

                            <a
                                href="pages/cart.php"
                                class="header-action cart-action"
                            >

                                <i class="bi bi-cart3"></i>

                                <span>Cart</span>

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
                            <a
                                href="index.php"
                                class="active"
                            >
                                Home
                            </a>
                        </li>

                        <li>
                            <a href="pages/shop.php">
                                Shop
                            </a>
                        </li>

                        <li>
                            <a href="pages/deals.php">
                                Deals
                            </a>
                        </li>

                        <li>
                            <a href="pages/new-arrivals.php">
                                New Arrivals
                            </a>
                        </li>

                        <li>
                            <a href="pages/track-order.php">
                                Track Order
                            </a>
                        </li>

                        <li>
                            <a href="pages/help.php">
                                Help Center
                            </a>
                        </li>

                    </ul>

                </div>

            </nav>

        </header>


        <!-- =====================================================
             HERO SECTION
        ====================================================== -->

        <section class="hero-section">

            <div class="hero-pattern"></div>

            <div
                class="container-fluid bright-container hero-container"
            >

                <div class="row align-items-center g-0">


                    <!-- HERO TEXT -->

                    <div class="col-lg-6 hero-content">

                        <div class="welcome-text">
                            WELCOME TO BRIGHTBUY
                        </div>

                        <h1>
                            Smart Shopping,
                            <span>Brighter Living.</span>
                        </h1>

                        <p>
                            Discover a wide range of quality products
                            at the best prices, delivered to your door.
                        </p>

                        <a
                            href="pages/shop.php"
                            class="hero-button"
                        >

                            Shop Now

                            <i class="bi bi-chevron-right"></i>

                        </a>

                    </div>


                    <!-- HERO IMAGE -->

                    <div class="col-lg-6 hero-image-column">

                        <div class="hero-image-wrapper">

                            <img
                                src="images/hero.png"
                                alt="BrightBuy Shopping"
                                class="hero-image"
                            >

                        </div>

                    </div>

                </div>


                <!-- HERO FEATURES -->

                <div class="hero-features">

                    <div class="hero-feature">

                        <div class="hero-feature-icon">
                            <i class="bi bi-truck"></i>
                        </div>

                        <div>
                            <strong>Fast Delivery</strong>
                            <small>To your doorstep</small>
                        </div>

                    </div>


                    <div class="hero-feature">

                        <div class="hero-feature-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>

                        <div>
                            <strong>Secure Payment</strong>
                            <small>100% Protected</small>
                        </div>

                    </div>


                    <div class="hero-feature">

                        <div class="hero-feature-icon">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>

                        <div>
                            <strong>Easy Returns</strong>
                            <small>Hassle-free</small>
                        </div>

                    </div>


                    <div class="hero-feature">

                        <div class="hero-feature-icon">
                            <i class="bi bi-award"></i>
                        </div>

                        <div>
                            <strong>Best Quality</strong>
                            <small>Guaranteed</small>
                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- =====================================================
             CATEGORIES
        ====================================================== -->

        <section class="categories-section">

            <div class="container-fluid bright-container">

                <div class="section-heading-row">

                    <div>
                        <h2>Explore Top Categories</h2>
                    </div>

                    <a
                        href="pages/shop.php"
                        class="view-all-link"
                    >

                        View All Categories

                        <i class="bi bi-chevron-right"></i>

                    </a>

                </div>


                <div class="row category-row">

                    <?php foreach ($categories as $category): ?>

                        <div class="col-6 col-sm-4 col-lg-2">

                            <a
                                href="<?= htmlspecialchars(
                                    $category["link"]
                                ) ?>"
                                class="category-card"
                            >

                                <div class="category-image-wrapper">

                                    <img
                                        src="<?= htmlspecialchars(
                                            $category["image"]
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $category["name"]
                                        ) ?>"
                                        class="category-image"
                                        loading="lazy"
                                    >

                                </div>

                                <h3>
                                    <?= htmlspecialchars(
                                        $category["name"]
                                    ) ?>
                                </h3>

                                <span>
                                    <?= htmlspecialchars(
                                        $category["items"]
                                    ) ?>
                                </span>

                            </a>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </section>


        <!-- =====================================================
             FEATURED PRODUCTS
        ====================================================== -->

        <section class="featured-section">

            <div class="container-fluid bright-container">

                <div class="featured-box">

                    <div class="featured-heading">

                        <div>

                            <span class="small-label">
                                FEATURED PRODUCTS
                            </span>

                            <h2>
                                Best Picks for You
                            </h2>

                        </div>

                        <a
                            href="pages/shop.php"
                            class="view-products"
                        >

                            View All Products

                            <i class="bi bi-chevron-right"></i>

                        </a>

                    </div>


                    <!-- PRODUCT SLIDER -->

                    <div class="product-slider-wrapper">


                        <!-- PREVIOUS BUTTON -->

                        <button
                            type="button"
                            class="product-arrow product-prev"
                            aria-label="Previous products"
                        >

                            <i class="bi bi-chevron-left"></i>

                        </button>


                        <!-- PRODUCTS -->

                        <div
                            class="products-track"
                            id="productsTrack"
                        >

                            <?php foreach ($products as $product): ?>

                                <div class="product-card">


                                    <!-- DISCOUNT -->

                                    <div class="discount-badge">
                                        <?= htmlspecialchars(
                                            $product["discount"]
                                        ) ?>
                                    </div>


                                    <!-- WISHLIST BUTTON -->

                                    <button
    class="product-wishlist <?= in_array(
        (int)$product['product_id'],
        $wishlistProductIds,
        true
    ) ? 'active' : '' ?>"
    type="button"
    data-product-id="<?= (int)$product['product_id'] ?>"
    aria-label="<?= in_array(
        (int)$product['product_id'],
        $wishlistProductIds,
        true
    ) ? 'Remove from wishlist' : 'Add to wishlist' ?>"
    title="<?= in_array(
        (int)$product['product_id'],
        $wishlistProductIds,
        true
    ) ? 'Remove from Wishlist' : 'Add to Wishlist' ?>"
>
    <i class="bi <?= in_array(
        (int)$product['product_id'],
        $wishlistProductIds,
        true
    ) ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
</button>


                                    <!-- PRODUCT IMAGE -->

                                    <div class="product-image-box">

                                        <img
                                            src="<?= htmlspecialchars(
                                                $product["image"]
                                            ) ?>"
                                            alt="<?= htmlspecialchars(
                                                $product["name"]
                                            ) ?>"
                                            loading="lazy"
                                        >

                                    </div>


                                    <!-- PRODUCT INFORMATION -->

                                    <div class="product-info">

                                        <h3>
                                            <?= htmlspecialchars(
                                                $product["name"]
                                            ) ?>
                                        </h3>


                                        <!-- PRODUCT STOCK -->

                                        <?php if (
                                            (int) $product["stock"] > 10
                                        ): ?>

                                            <div
                                                class="product-stock stock-available"
                                            >

                                                <i
                                                    class="bi bi-check-circle-fill"
                                                ></i>

                                                <?= (int)
                                                    $product["stock"] ?>
                                                items left

                                            </div>

                                        <?php elseif (
                                            (int) $product["stock"] > 0
                                        ): ?>

                                            <div
                                                class="product-stock stock-low"
                                            >

                                                <i
                                                    class="bi bi-exclamation-circle-fill"
                                                ></i>

                                                Only
                                                <?= (int)
                                                    $product["stock"] ?>
                                                left

                                            </div>

                                        <?php else: ?>

                                            <div
                                                class="product-stock stock-out"
                                            >

                                                <i
                                                    class="bi bi-x-circle-fill"
                                                ></i>

                                                Out of Stock

                                            </div>

                                        <?php endif; ?>


                                        <!-- PRODUCT RATING -->

                                        <div class="rating">

                                            <span class="stars">
                                                ★★★★★
                                            </span>

                                            <small>

                                                <?= htmlspecialchars(
                                                    $product["rating"]
                                                ) ?>

                                                (
                                                <?= htmlspecialchars(
                                                    $product["reviews"]
                                                ) ?>
                                                )

                                            </small>

                                        </div>


                                        <!-- PRICE AND CART -->

                                        <div class="product-bottom">

                                            <div>

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $product["price"]
                                                    ) ?>
                                                </strong>

                                                <del>
                                                    <?= htmlspecialchars(
                                                        $product["old_price"]
                                                    ) ?>
                                                </del>

                                            </div>


                                            <!-- ADD TO CART -->

                                            <?php if (
                                                (int) $product["stock"] > 0
                                            ): ?>

                                                <form
                                                    method="POST"
                                                    action="actions/add-to-cart.php"
                                                    class="add-cart-form"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="product_id"
                                                        value="<?= (int)
                                                            $product["product_id"] ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="add-to-cart"
                                                        aria-label="Add <?= htmlspecialchars(
                                                            $product["name"]
                                                        ) ?> to cart"
                                                    >

                                                        <i
                                                            class="bi bi-cart-plus"
                                                        ></i>

                                                    </button>

                                                </form>

                                            <?php else: ?>

                                                <button
                                                    type="button"
                                                    class="add-to-cart"
                                                    disabled
                                                    aria-label="Out of stock"
                                                >

                                                    <i
                                                        class="bi bi-x-circle"
                                                    ></i>

                                                </button>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>


                        <!-- NEXT BUTTON -->

                        <button
                            type="button"
                            class="product-arrow product-next"
                            aria-label="Next products"
                        >

                            <i class="bi bi-chevron-right"></i>

                        </button>

                    </div>

                </div>

            </div>

        </section>


        <!-- =====================================================
             LOWER HOMEPAGE SECTION
        ====================================================== -->

        <div class="lower-home-section">

            <div class="yellow-curve-left"></div>

            <div class="yellow-curve-right"></div>


            <!-- =================================================
                 SPECIAL OFFER SLIDER
            ================================================== -->

            <section class="offer-section">

                <div class="offer-slider">


                    <!-- SLIDE 1 -->

                    <div class="offer-slide active">

                        <div class="offer-banner">

                            <div class="offer-text">

                                <span>SPECIAL OFFER</span>

                                <h2>
                                    Big Deals,<br>
                                    Bigger Savings!
                                </h2>

                                <p>
                                    Get up to 50% OFF on selected items.<br>
                                    Limited time only!
                                </p>

                                <a
                                    href="pages/deals.php"
                                    class="offer-button"
                                >

                                    <span>Shop Deals</span>

                                    <i class="bi bi-arrow-right"></i>

                                </a>

                            </div>


                            <div class="offer-graphic">

                                <img
                                    src="images/offer.png"
                                    alt="Big Deals"
                                >

                            </div>

                        </div>

                    </div>


                    <!-- SLIDE 2 -->

                    <div class="offer-slide">

                        <div class="offer-banner">

                            <div class="offer-text">

                                <span>HOT DEALS</span>

                                <h2>
                                    Save More,<br>
                                    Shop More!
                                </h2>

                                <p>
                                    Enjoy amazing discounts on selected
                                    products.<br>
                                    Don't miss these limited-time deals!
                                </p>

                                <a
                                    href="pages/deals.php"
                                    class="offer-button"
                                >

                                    <span>View Deals</span>

                                    <i class="bi bi-arrow-right"></i>

                                </a>

                            </div>


                            <div class="offer-graphic">

                                <img
                                    src="images/offer2.png"
                                    alt="Hot Deals"
                                >

                            </div>

                        </div>

                    </div>


                    <!-- SLIDE 3 -->

                    <div class="offer-slide">

                        <div class="offer-banner">

                            <div class="offer-text">

                                <span>LIMITED TIME</span>

                                <h2>
                                    Exclusive Deals,<br>
                                    Just For You!
                                </h2>

                                <p>
                                    Discover great products at incredible
                                    prices.<br>
                                    Shop now while supplies last!
                                </p>

                                <a
                                    href="pages/deals.php"
                                    class="offer-button"
                                >

                                    <span>Shop Now</span>

                                    <i class="bi bi-arrow-right"></i>

                                </a>

                            </div>


                            <div class="offer-graphic">

                                <img
                                    src="images/offer3.png"
                                    alt="Exclusive Deals"
                                >

                            </div>

                        </div>

                    </div>


                    <!-- SLIDER DOTS -->

                    <div class="offer-dots">

                        <button
                            type="button"
                            class="offer-dot active"
                            data-slide="0"
                            aria-label="Slide 1"
                        ></button>

                        <button
                            type="button"
                            class="offer-dot"
                            data-slide="1"
                            aria-label="Slide 2"
                        ></button>

                        <button
                            type="button"
                            class="offer-dot"
                            data-slide="2"
                            aria-label="Slide 3"
                        ></button>

                    </div>

                </div>

            </section>


            <!-- =================================================
                 WHY SHOP WITH US
            ================================================== -->

            <section class="why-section">

                <div class="why-container">


                    <!-- SECTION HEADING -->

                    <div class="why-heading">

                        <span>WHY SHOP WITH US</span>

                        <h2>
                            We Make Shopping Better
                        </h2>

                        <div class="why-heading-line"></div>

                    </div>


                    <!-- BENEFITS -->

                    <div class="why-benefits">


                        <!-- BEST QUALITY -->

                        <div class="why-benefit-card">

                            <div class="why-benefit-icon">
                                <i class="bi bi-award"></i>
                            </div>

                            <div class="why-benefit-content">

                                <h3>Best Quality</h3>

                                <p>
                                    We offer top-quality
                                    products you can trust.
                                </p>

                            </div>

                        </div>


                        <!-- BEST PRICES -->

                        <div class="why-benefit-card">

                            <div class="why-benefit-icon">
                                <i class="bi bi-tag"></i>
                            </div>

                            <div class="why-benefit-content">

                                <h3>Best Prices</h3>

                                <p>
                                    Competitive prices
                                    that fit your budget.
                                </p>

                            </div>

                        </div>


                        <!-- FAST DELIVERY -->

                        <div class="why-benefit-card">

                            <div class="why-benefit-icon">
                                <i class="bi bi-truck"></i>
                            </div>

                            <div class="why-benefit-content">

                                <h3>Fast Delivery</h3>

                                <p>
                                    Quick and reliable
                                    delivery to your door.
                                </p>

                            </div>

                        </div>


                        <!-- SECURE PAYMENTS -->

                        <div class="why-benefit-card">

                            <div class="why-benefit-icon">
                                <i class="bi bi-shield-check"></i>
                            </div>

                            <div class="why-benefit-content">

                                <h3>Secure Payments</h3>

                                <p>
                                    Safe and secure
                                    payment methods.
                                </p>

                            </div>

                        </div>


                        <!-- 24/7 SUPPORT -->

                        <div class="why-benefit-card">

                            <div class="why-benefit-icon">
                                <i class="bi bi-headset"></i>
                            </div>

                            <div class="why-benefit-content">

                                <h3>24/7 Support</h3>

                                <p>
                                    We're here to help
                                    you anytime.
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </section>

        </div>


        <!-- =====================================================
             TESTIMONIALS
        ====================================================== -->

        <section class="testimonial-section">

            <div class="testimonial-container">


                <!-- TESTIMONIAL HEADING -->

                <div class="testimonial-heading">

                    <span>WHAT OUR CUSTOMERS SAY</span>

                    <h2>
                        Trusted by Thousands
                    </h2>

                    <div class="testimonial-stars">
                        ★★★★★
                    </div>

                </div>


                <!-- TESTIMONIAL CARDS -->

                <div class="testimonial-row">


                    <!-- TESTIMONIAL 1 -->

                    <div class="testimonial-card">

                        <div class="quote-icon">
                            “
                        </div>

                        <p class="testimonial-message">
                            BrightBuy has amazing products at the best prices.
                            Delivery is fast and customer service is excellent!
                        </p>

                        <div class="customer-info">

                            <div class="customer-avatar">
                                <i class="bi bi-person-fill"></i>
                            </div>

                            <div class="customer-details">

                                <h3>
                                    John D.
                                </h3>

                                <span>
                                    Verified Buyer
                                </span>

                            </div>

                        </div>

                    </div>


                    <!-- TESTIMONIAL 2 -->

                    <div class="testimonial-card">

                        <div class="quote-icon">
                            “
                        </div>

                        <p class="testimonial-message">
                            I love how easy it is to shop here.
                            Everything I need in one place with great deals
                            every day!
                        </p>

                        <div class="customer-info">

                            <div class="customer-avatar">
                                <i class="bi bi-person-fill"></i>
                            </div>

                            <div class="customer-details">

                                <h3>
                                    Maria S.
                                </h3>

                                <span>
                                    Verified Buyer
                                </span>

                            </div>

                        </div>

                    </div>


                    <!-- TESTIMONIAL 3 -->

                    <div class="testimonial-card">

                        <div class="quote-icon">
                            “
                        </div>

                        <p class="testimonial-message">
                            Quality products, secure payments,
                            and fast delivery. Highly recommended!
                        </p>

                        <div class="customer-info">

                            <div class="customer-avatar">
                                <i class="bi bi-person-fill"></i>
                            </div>

                            <div class="customer-details">

                                <h3>
                                    Mark T.
                                </h3>

                                <span>
                                    Verified Buyer
                                </span>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- TESTIMONIAL INDICATORS -->

                <div class="testimonial-dots">

                    <span class="active"></span>
                    <span></span>
                    <span></span>
                    <span></span>

                </div>

            </div>

        </section>


        <!-- =====================================================
             FOOTER
        ====================================================== -->

        <footer class="site-footer">

            <div class="container-fluid bright-container">

                <div class="footer-main">


                    <!-- BRAND -->

                    <div class="footer-brand">

                        <a href="index.php">

                            <img
                                src="images/logo.png"
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

                        <h3>Shop</h3>

                        <a href="pages/shop.php">
                            All Categories
                        </a>

                        <a href="pages/deals.php">
                            Deals
                        </a>

                        <a href="pages/new-arrivals.php">
                            New Arrivals
                        </a>

                        <a href="pages/shop.php">
                            Best Sellers
                        </a>

                    </div>


                    <!-- CUSTOMER SERVICES -->

                    <div class="footer-column">

                        <h3>
                            Customer Services
                        </h3>

                        <a href="pages/help.php">
                            Help Center
                        </a>

                        <a href="pages/track-order.php">
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

                        <a href="#">
                            My Orders
                        </a>

                        <a href="#">
                            Wishlist
                        </a>

                        <a href="#">
                            Account Settings
                        </a>

                    </div>


                    <!-- MOBILE APP -->

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

                        <span>VISA</span>
                        <span>●●</span>
                        <span>PayPal</span>
                        <span>GPay</span>

                    </div>

                </div>

            </div>

        </footer>

    </div>

</div>


<!-- =========================================================
     BACK TO TOP BUTTON
========================================================= -->

<button
    type="button"
    id="backToTop"
    aria-label="Back to top"
>

    <i class="bi bi-arrow-up"></i>

</button>


<!-- =========================================================
     BOOTSTRAP JAVASCRIPT
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<!-- =========================================================
     BRIGHTBUY CUSTOM JAVASCRIPT
========================================================= -->

<script src="js/script.js"></script>

</body>

</html>