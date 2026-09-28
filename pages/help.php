<?php

session_start();

require_once "../db.php";


/* =====================================================
   WISHLIST COUNT
===================================================== */

$wishlistCount = 0;

if (
    isset($_SESSION["logged_in"]) &&
    $_SESSION["logged_in"] === true
) {

    $user_id = (int)$_SESSION["user_id"];

    $wishlistStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM wishlist
        WHERE user_id = ?
    ");

    $wishlistStmt->execute([$user_id]);

    $wishlistCount = (int)$wishlistStmt->fetchColumn();

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

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Help Center | BrightBuy
    </title>


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


    <!-- Poppins -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- BrightBuy CSS -->

    <link
        rel="stylesheet"
        href="../css/styles.css"
    >

</head>


<body class="help-page">


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

        <div class="bright-container">

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
                    action="shop.php"
                    method="GET"
                    class="search-box"
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

        <div class="bright-container">

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

                    <a href="new-arrivals.php">
                        New Arrivals
                    </a>

                </li>


                <li>

                    <a href="track-order.php">
                        Track Order
                    </a>

                </li>


                <li>

                    <a
                        href="help.php"
                        class="active"
                    >
                        Help Center
                    </a>

                </li>


            </ul>

        </div>

    </nav>

</header>


<!-- =====================================================
     HELP CENTER HERO
===================================================== -->

<section class="help-hero">

    <div class="help-hero-content">


        <div class="help-hero-label">
            CUSTOMER SUPPORT
        </div>


        <h1>
            Help <span>Center</span>
        </h1>


        <p>
            Find answers to common questions and get the help
            you need for your BrightBuy shopping experience.
        </p>


    </div>

</section>


<!-- =====================================================
     HELP CENTER CONTENT
===================================================== -->

<main class="help-content">


    <!-- =================================================
         SEARCH HELP
    ================================================== -->

    <section class="help-search-card">

        <div class="help-search-icon">

            <i class="bi bi-question-circle"></i>

        </div>


        <div class="help-search-text">

            <h2>
                How can we help?
            </h2>

            <p>
                Search our frequently asked questions
                to find quick answers.
            </p>

        </div>


        <form
            class="help-search-form"
            onsubmit="return false;"
        >

            <i class="bi bi-search"></i>

            <input
                type="text"
                id="helpSearch"
                placeholder="Search for help..."
                autocomplete="off"
            >

        </form>

    </section>


    <!-- =================================================
         HELP CATEGORIES
    ================================================== -->

    <section class="help-categories-section">


        <div class="help-section-heading">

            <span>
                SUPPORT TOPICS
            </span>

            <h2>
                Browse Help Topics
            </h2>

            <p>
                Find information about your BrightBuy account,
                orders, payments, and more.
            </p>

        </div>


        <div class="help-category-grid">


            <a
                href="#orders"
                class="help-category-card"
            >

                <div class="help-category-icon">
                    <i class="bi bi-bag-check"></i>
                </div>

                <h3>
                    Orders
                </h3>

                <p>
                    Learn about placing and managing orders.
                </p>

            </a>


            <a
                href="#shipping"
                class="help-category-card"
            >

                <div class="help-category-icon">
                    <i class="bi bi-truck"></i>
                </div>

                <h3>
                    Shipping & Delivery
                </h3>

                <p>
                    Get information about delivery and tracking.
                </p>

            </a>


            <a
                href="#payments"
                class="help-category-card"
            >

                <div class="help-category-icon">
                    <i class="bi bi-credit-card"></i>
                </div>

                <h3>
                    Payments
                </h3>

                <p>
                    Find answers about payments and billing.
                </p>

            </a>


            <a
                href="#returns"
                class="help-category-card"
            >

                <div class="help-category-icon">
                    <i class="bi bi-arrow-return-left"></i>
                </div>

                <h3>
                    Returns & Refunds
                </h3>

                <p>
                    Learn about returns and refund requests.
                </p>

            </a>


            <a
                href="#account"
                class="help-category-card"
            >

                <div class="help-category-icon">
                    <i class="bi bi-person-circle"></i>
                </div>

                <h3>
                    Account
                </h3>

                <p>
                    Manage your BrightBuy account and profile.
                </p>

            </a>


            <a
                href="#wishlist"
                class="help-category-card"
            >

                <div class="help-category-icon">
                    <i class="bi bi-heart"></i>
                </div>

                <h3>
                    Wishlist
                </h3>

                <p>
                    Learn how to save and manage favorite products.
                </p>

            </a>


        </div>

    </section>


    <!-- =================================================
         FAQ
    ================================================== -->

    <section class="faq-section">

        <div class="help-section-heading">

            <span>
                FREQUENTLY ASKED QUESTIONS
            </span>

            <h2>
                Common Questions
            </h2>

            <p>
                Here are answers to some of the most common
                BrightBuy questions.
            </p>

        </div>


        <div
            class="accordion help-accordion"
            id="helpAccordion"
        >


            <!-- ORDERS -->

            <div
                class="accordion-item"
                id="orders"
            >

                <h2 class="accordion-header">

                    <button
                        class="accordion-button"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#faqOrders"
                    >

                        How do I place an order?

                    </button>

                </h2>


                <div
                    id="faqOrders"
                    class="accordion-collapse collapse show"
                    data-bs-parent="#helpAccordion"
                >

                    <div class="accordion-body">

                        Browse the products available on BrightBuy,
                        select the item you want, and add it to your
                        cart. When you're ready, open your cart and
                        proceed to checkout. Review your order details
                        and complete the order.

                    </div>

                </div>

            </div>


            <!-- TRACKING -->

            <div
                class="accordion-item"
                id="shipping"
            >

                <h2 class="accordion-header">

                    <button
                        class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#faqTracking"
                    >

                        How can I track my order?

                    </button>

                </h2>


                <div
                    id="faqTracking"
                    class="accordion-collapse collapse"
                    data-bs-parent="#helpAccordion"
                >

                    <div class="accordion-body">

                        Go to the Track Order page from the main
                        navigation or open an order from My Orders.
                        Your order status will show the current
                        progress from Pending to Processing, Shipped,
                        and Delivered.

                    </div>

                </div>

            </div>


            <!-- PAYMENT -->

            <div
                class="accordion-item"
                id="payments"
            >

                <h2 class="accordion-header">

                    <button
                        class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#faqPayment"
                    >

                        What payment methods are available?

                    </button>

                </h2>


                <div
                    id="faqPayment"
                    class="accordion-collapse collapse"
                    data-bs-parent="#helpAccordion"
                >

                    <div class="accordion-body">

                        Available payment options are shown during
                        the checkout process. Select your preferred
                        payment method before placing your order.

                    </div>

                </div>

            </div>


            <!-- RETURNS -->

            <div
                class="accordion-item"
                id="returns"
            >

                <h2 class="accordion-header">

                    <button
                        class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#faqReturns"
                    >

                        How do returns and refunds work?

                    </button>

                </h2>


                <div
                    id="faqReturns"
                    class="accordion-collapse collapse"
                    data-bs-parent="#helpAccordion"
                >

                    <div class="accordion-body">

                        For questions about returning an item or
                        requesting a refund, contact BrightBuy
                        customer support with your order information
                        so your request can be reviewed.

                    </div>

                </div>

            </div>


            <!-- ACCOUNT -->

            <div
                class="accordion-item"
                id="account"
            >

                <h2 class="accordion-header">

                    <button
                        class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#faqAccount"
                    >

                        How do I manage my account?

                    </button>

                </h2>


                <div
                    id="faqAccount"
                    class="accordion-collapse collapse"
                    data-bs-parent="#helpAccordion"
                >

                    <div class="accordion-body">

                        Sign in to your BrightBuy customer account
                        and open Account from the header. From there,
                        you can review your account information and
                        manage your available account settings.

                    </div>

                </div>

            </div>


            <!-- WISHLIST -->

            <div
                class="accordion-item"
                id="wishlist"
            >

                <h2 class="accordion-header">

                    <button
                        class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#faqWishlist"
                    >

                        How do I add a product to my wishlist?

                    </button>

                </h2>


                <div
                    id="faqWishlist"
                    class="accordion-collapse collapse"
                    data-bs-parent="#helpAccordion"
                >

                    <div class="accordion-body">

                        Click the heart icon on a product to add it
                        to your wishlist. You can open Wishlist from
                        the header to view the products you've saved.

                    </div>

                </div>

            </div>


        </div>

    </section>


    <!-- =================================================
         CONTACT SUPPORT
    ================================================== -->

    <section class="help-contact-section">


        <div class="help-contact-card">


            <div class="help-contact-icon">

                <i class="bi bi-headset"></i>

            </div>


            <div class="help-contact-text">

                <span>
                    STILL NEED HELP?
                </span>

                <h2>
                    We're here to help.
                </h2>

                <p>
                    Can't find the answer you're looking for?
                    Reach out to the BrightBuy support team.
                </p>

            </div>


            <a
                href="mailto:support@brightbuy.com"
                class="help-contact-button"
            >

                <i class="bi bi-envelope"></i>

                Contact Support

            </a>


        </div>


    </section>


</main>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="site-footer">

    <div class="bright-container">

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
     BOOTSTRAP JS
===================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<!-- =====================================================
     HELP SEARCH
===================================================== -->

<script>

const helpSearch =
    document.getElementById("helpSearch");

const faqItems =
    document.querySelectorAll(
        ".help-accordion .accordion-item"
    );


helpSearch.addEventListener(
    "input",
    function () {

        const search =
            this.value.toLowerCase().trim();


        faqItems.forEach(
            item => {

                const text =
                    item.textContent.toLowerCase();


                if (
                    search === "" ||
                    text.includes(search)
                ) {

                    item.style.display = "";

                } else {

                    item.style.display = "none";

                }

            }
        );

    }
);

</script>


</body>

</html>