<?php

session_start();

require_once "../db.php";


/* =====================================================
   CUSTOMER LOGIN CHECK
===================================================== */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}


/* =====================================================
   USER ID
===================================================== */

$user_id = (int)$_SESSION["user_id"];


/* =====================================================
   GET ORDER ID
===================================================== */

$order_id = filter_input(
    INPUT_GET,
    "order_id",
    FILTER_VALIDATE_INT
);


/* =====================================================
   WISHLIST COUNT
===================================================== */

$wishlistCount = 0;

$wishlistProductIds = [];


$wishlistStmt = $pdo->prepare("
    SELECT product_id
    FROM wishlist
    WHERE user_id = ?
");

$wishlistStmt->execute([
    $user_id
]);

$wishlistProductIds = $wishlistStmt->fetchAll(
    PDO::FETCH_COLUMN
);

$wishlistCount = count($wishlistProductIds);


/* =====================================================
   CART COUNT
===================================================== */

$cartCount = 0;


$cartStmt = $pdo->prepare("
    SELECT COALESCE(SUM(ci.quantity), 0)
    FROM cart_items ci
    INNER JOIN cart c
        ON ci.cart_id = c.cart_id
    WHERE c.user_id = ?
");

$cartStmt->execute([
    $user_id
]);

$cartCount = (int)$cartStmt->fetchColumn();


/* =====================================================
   GET ORDER
===================================================== */

$order = null;

$orderItems = [];


if ($order_id) {

    $orderStmt = $pdo->prepare("
        SELECT
            o.order_id,
            o.total_amount,
            o.status,
            o.created_at,

            a.recipient_name,
            a.phone,
            a.address_line,
            a.barangay,
            a.city,
            a.province,
            a.postal_code

        FROM orders o

        LEFT JOIN addresses a
            ON o.address_id = a.address_id

        WHERE o.order_id = ?
          AND o.user_id = ?

        LIMIT 1
    ");


    $orderStmt->execute([
        $order_id,
        $user_id
    ]);


    $order = $orderStmt->fetch();


    /* =================================================
       GET ORDER ITEMS
    ================================================= */

    if ($order) {

        $itemsStmt = $pdo->prepare("
            SELECT
                oi.quantity,
                oi.price,
                oi.subtotal,

                p.product_name,
                p.image

            FROM order_items oi

            INNER JOIN products p
                ON oi.product_id = p.product_id

            WHERE oi.order_id = ?
        ");


        $itemsStmt->execute([
            $order_id
        ]);


        $orderItems = $itemsStmt->fetchAll();

    }

}


/* =====================================================
   ORDER STATUS
===================================================== */

$status = $order
    ? strtolower(trim($order["status"]))
    : "";

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
        Track Order - BrightBuy
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


<body class="track-order-page">


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

                    <a
                        href="track-order.php"
                        class="active"
                    >
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
     TRACK ORDER HERO
===================================================== -->

<section class="track-hero">

    <div class="track-hero-content">

        <div class="track-hero-label">
            TRACKING
        </div>

        <h1>
            Track <span>Your Order</span>
        </h1>

        <p>
            Check the current status of your BrightBuy order
            and stay updated every step of the way.
        </p>

    </div>

</section>


<!-- =====================================================
     TRACK ORDER CONTENT
===================================================== -->

<main class="track-content">


<?php if (!$order): ?>


    <!-- =================================================
         NO ORDER SELECTED
    ================================================== -->

    <div class="tracking-card">

        <div class="track-empty">

            <div class="track-empty-icon">

                <i class="bi bi-search"></i>

            </div>


            <h3>
                Select an Order
            </h3>


            <p>
                Go to My Orders and select an order to track.
            </p>


            <a
                href="my-orders.php"
                class="track-primary-btn"
            >

                <i class="bi bi-bag-check"></i>

                My Orders

            </a>

        </div>

    </div>


<?php else: ?>


    <!-- =================================================
         ORDER INFORMATION
    ================================================== -->

    <div class="tracking-card order-summary-card">

        <div class="order-summary">


            <div>

                <span class="track-small-label">
                    ORDER NUMBER
                </span>

                <h3>
                    Order #<?= (int)$order["order_id"] ?>
                </h3>

                <p>

                    <?= date(
                        "F d, Y h:i A",
                        strtotime($order["created_at"])
                    ) ?>

                </p>

            </div>


            <span class="track-status-badge">

                <?= htmlspecialchars(
                    $order["status"]
                ) ?>

            </span>


        </div>

    </div>


    <!-- =================================================
         ORDER STATUS
    ================================================== -->

    <div class="tracking-card status-card">

        <div class="tracking-card-body">

            <h3 class="tracking-section-title">
                Order Status
            </h3>


            <div class="tracking-progress">


                <!-- PENDING -->

                <div
                    class="tracking-step
                    <?= in_array(
                        $status,
                        [
                            "pending",
                            "processing",
                            "shipped",
                            "delivered"
                        ]
                    )
                        ? "completed"
                        : ""
                    ?>"
                >

                    <div class="tracking-icon">

                        <i class="bi bi-clock"></i>

                    </div>

                    <h6>
                        Pending
                    </h6>

                </div>


                <div
                    class="tracking-line
                    <?= in_array(
                        $status,
                        [
                            "processing",
                            "shipped",
                            "delivered"
                        ]
                    )
                        ? "completed"
                        : ""
                    ?>"
                ></div>


                <!-- PROCESSING -->

                <div
                    class="tracking-step
                    <?= in_array(
                        $status,
                        [
                            "processing",
                            "shipped",
                            "delivered"
                        ]
                    )
                        ? "completed"
                        : ""
                    ?>"
                >

                    <div class="tracking-icon">

                        <i class="bi bi-gear"></i>

                    </div>

                    <h6>
                        Processing
                    </h6>

                </div>


                <div
                    class="tracking-line
                    <?= in_array(
                        $status,
                        [
                            "shipped",
                            "delivered"
                        ]
                    )
                        ? "completed"
                        : ""
                    ?>"
                ></div>


                <!-- SHIPPED -->

                <div
                    class="tracking-step
                    <?= in_array(
                        $status,
                        [
                            "shipped",
                            "delivered"
                        ]
                    )
                        ? "completed"
                        : ""
                    ?>"
                >

                    <div class="tracking-icon">

                        <i class="bi bi-truck"></i>

                    </div>

                    <h6>
                        Shipped
                    </h6>

                </div>


                <div
                    class="tracking-line
                    <?= $status === "delivered"
                        ? "completed"
                        : ""
                    ?>"
                ></div>


                <!-- DELIVERED -->

                <div
                    class="tracking-step
                    <?= $status === "delivered"
                        ? "completed"
                        : ""
                    ?>"
                >

                    <div class="tracking-icon">

                        <i class="bi bi-check-circle"></i>

                    </div>

                    <h6>
                        Delivered
                    </h6>

                </div>

            </div>

        </div>

    </div>


    <!-- =================================================
         ORDERED ITEMS
    ================================================== -->

    <div class="tracking-card">

        <div class="tracking-card-body">

            <h3 class="tracking-section-title">
                Ordered Items
            </h3>


            <div class="ordered-items">


                <?php foreach ($orderItems as $item): ?>

                    <div class="ordered-item">


                        <div class="ordered-product">


                            <img
                                src="../<?= htmlspecialchars(
                                    $item["image"]
                                ) ?>"
                                alt="<?= htmlspecialchars(
                                    $item["product_name"]
                                ) ?>"
                            >


                            <div>

                                <h6>

                                    <?= htmlspecialchars(
                                        $item["product_name"]
                                    ) ?>

                                </h6>


                                <p>

                                    ₱<?= number_format(
                                        $item["price"],
                                        2
                                    ) ?>

                                    ×

                                    <?= (int)$item["quantity"] ?>

                                </p>

                            </div>

                        </div>


                        <strong>

                            ₱<?= number_format(
                                $item["subtotal"],
                                2
                            ) ?>

                        </strong>


                    </div>

                <?php endforeach; ?>


            </div>


            <!-- ORDER TOTAL -->

            <div class="order-total">

                <strong>
                    Order Total
                </strong>

                <strong>

                    ₱<?= number_format(
                        $order["total_amount"],
                        2
                    ) ?>

                </strong>

            </div>

        </div>

    </div>


    <!-- =================================================
         DELIVERY ADDRESS
    ================================================== -->

    <div class="tracking-card">

        <div class="tracking-card-body">

            <h3 class="tracking-section-title">

                <i class="bi bi-geo-alt"></i>

                Delivery Address

            </h3>


            <div class="delivery-address">


                <p class="address-name">

                    <?= htmlspecialchars(
                        $order["recipient_name"] ?? ""
                    ) ?>

                </p>


                <p>

                    <?= htmlspecialchars(
                        $order["phone"] ?? ""
                    ) ?>

                </p>


                <p>

                    <?= htmlspecialchars(
                        $order["address_line"] ?? ""
                    ) ?>

                </p>


                <p>

                    <?= htmlspecialchars(
                        $order["barangay"] ?? ""
                    ) ?>,

                    <?= htmlspecialchars(
                        $order["city"] ?? ""
                    ) ?>

                </p>


                <p>

                    <?= htmlspecialchars(
                        $order["province"] ?? ""
                    ) ?>

                    <?php if (
                        !empty($order["postal_code"])
                    ): ?>

                        <?= htmlspecialchars(
                            $order["postal_code"]
                        ) ?>

                    <?php endif; ?>

                </p>


            </div>

        </div>

    </div>


<?php endif; ?>


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
     BOOTSTRAP JS
===================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<!-- =====================================================
     BRIGHTBUY JS
===================================================== -->

<script src="../js/script.js"></script>


</body>

</html>