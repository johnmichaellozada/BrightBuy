<?php

session_start();

require_once "../db.php";


/* =====================================================
   WISHLIST
===================================================== */

$wishlistProductIds = [];

if (
    isset($_SESSION["logged_in"]) &&
    $_SESSION["logged_in"] === true
) {

    $wishlistStmt = $pdo->prepare("
        SELECT product_id
        FROM wishlist
        WHERE user_id = ?
    ");

    $wishlistStmt->execute([
        $_SESSION["user_id"]
    ]);

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

    $cartCount = (int)$cartCountStmt->fetchColumn();
}


/* =====================================================
   SEARCH
===================================================== */

$search = trim($_GET["search"] ?? "");


/* =====================================================
   CATEGORY
===================================================== */

$category = trim($_GET["category"] ?? "");


/* =====================================================
   HOMEPAGE CATEGORY MAPPING
===================================================== */

$categoryMap = [

    "electronics" => "Electronics",

    "fashion" => "Fashion",

    "home" => "Home & Living",

    "beauty" => "Beauty & Health",

    "sports" => "Sports & Outdoors",

    "toys" => "Toys & Games"

];


$categoryFilter = $categoryMap[
    strtolower($category)
] ?? $category;


/* =====================================================
   GET CATEGORIES
===================================================== */

$categoryStmt = $pdo->query("
    SELECT
        category_id,
        category_name
    FROM categories
    ORDER BY category_name ASC
");

$categories = $categoryStmt->fetchAll();


/* =====================================================
   GET PRODUCTS
===================================================== */

$sql = "
    SELECT
        p.product_id,
        p.product_name,
        p.description,
        p.price,
        p.stock,
        p.image,
        p.status,
        c.category_id,
        c.category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.category_id
    WHERE p.status = 'Active'
";

$params = [];


/* SEARCH FILTER */

if ($search !== "") {

    $sql .= "
        AND (
            p.product_name LIKE ?
            OR p.description LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
}


/* CATEGORY FILTER */

if ($categoryFilter !== "") {

    $sql .= "
        AND LOWER(c.category_name) = LOWER(?)
    ";

    $params[] = $categoryFilter;
}


$sql .= "
    ORDER BY p.product_id ASC
";


$productStmt = $pdo->prepare($sql);

$productStmt->execute($params);

$products = $productStmt->fetchAll();


/* =====================================================
   PAGE TITLE
===================================================== */

if ($search !== "") {

    $pageTitle = "Search Results for \"" .
        htmlspecialchars($search) .
        "\"";

} elseif ($categoryFilter !== "") {

    $pageTitle = htmlspecialchars($categoryFilter);

} else {

    $pageTitle = "All Products";
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
        BrightBuy | Shop
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


<body class="shop-page">

<!-- =====================================================
     BRIGHTBUY HEADER
===================================================== -->

<!-- =========================================================
     BRIGHTBUY HEADER - SAME AS HOME
========================================================= -->

<div class="brightbuy-viewport">

    <div class="brightbuy-canvas">

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
                                value="<?= htmlspecialchars($search) ?>"
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

                                <!-- LOGGED-IN ACCOUNT -->

                                <div class="d-flex align-items-center gap-2">

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


                                <!-- LOGGED-OUT ACCOUNT -->

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


            <!-- NAVIGATION -->

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

                            <a
                                href="shop.php"
                                class="active"
                            >

                                Shop

                            </a>

                        </li>


                        <!-- DEALS -->

                        <li>

                            <a href="deals.php">

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
     SHOP HERO
===================================================== -->

<section class="shop-hero">

    <div class="shop-hero-content">

        <h1>
            Shop <span>BrightBuy</span>
        </h1>

        <p>
            Discover quality products at prices you'll love.
        </p>

    </div>

</section>


<!-- =====================================================
     SHOP CONTENT
===================================================== -->

<main class="shop-container">


    <!-- FILTER BAR -->

    <div class="shop-filter-bar">

        <form
            method="GET"
            action="shop.php"
            class="shop-search"
        >

            <input
                type="text"
                name="search"
                value="<?= htmlspecialchars($search) ?>"
                placeholder="Search for products..."
            >

            <button type="submit">

                <i class="bi bi-search"></i>

            </button>

        </form>


        <!-- CATEGORY FILTER -->

        <div class="category-filter">

            <a
                href="shop.php"
                class="<?= $category === "" ? "active" : "" ?>"
            >
                <i class="bi bi-grid-fill"></i>
                All Products
            </a>


            <?php foreach ($categories as $cat): ?>

                <?php

                    $categorySlug =
                        strtolower(
                            trim(
                                $cat["category_name"]
                            )
                        );

                ?>

                <a
                    href="shop.php?category=<?= urlencode($cat["category_name"]) ?>"
                    class="<?= strtolower($category) === $categorySlug ? "active" : "" ?>"
                >

                    <i class="bi bi-tag"></i>

                    <?= htmlspecialchars($cat["category_name"]) ?>

                </a>

            <?php endforeach; ?>

        </div>

    </div>


    <!-- RESULTS HEADER -->

    <div class="shop-results-header">

        <div>

            <h2>
                <?= $pageTitle ?>
            </h2>

        </div>


        <div class="product-count">

            <?= count($products) ?>
            product<?= count($products) !== 1 ? "s" : "" ?>

        </div>

    </div>


    <!-- =================================================
         PRODUCTS
    ================================================= -->

    <?php if (!empty($products)): ?>

        <div class="shop-product-grid">


            <?php foreach ($products as $product): ?>

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


                <article class="shop-product-card">


                    <!-- PRODUCT IMAGE -->

                    <div class="shop-product-image">


                        <?php if ($stock <= 0): ?>

                            <span class="discount-badge">
                                OUT OF STOCK
                            </span>

                        <?php endif; ?>


                        <img
                            src="<?= htmlspecialchars($image) ?>"
                            alt="<?= htmlspecialchars($product["product_name"]) ?>"
                        >


                        <!-- WISHLIST -->

                        <button
                            type="button"
                            class="wishlist-button <?= $inWishlist ? "active" : "" ?>"
                            data-product-id="<?= $productId ?>"
                            aria-label="Add to wishlist"
                        >

                            <i
                                class="bi <?= $inWishlist ? "bi-heart-fill" : "bi-heart" ?>"
                            ></i>

                        </button>


                    </div>


                    <!-- PRODUCT INFORMATION -->

                    <div class="shop-product-info">


                        <?php if (!empty($product["category_name"])): ?>

                            <div class="shop-category">

                                <?= htmlspecialchars(
                                    $product["category_name"]
                                ) ?>

                            </div>

                        <?php endif; ?>


                        <div class="shop-product-name">

                            <?= htmlspecialchars(
                                $product["product_name"]
                            ) ?>

                        </div>


                        <!-- RATING -->

                        <div class="shop-rating">

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

                        <div class="shop-price-row">

                            <div class="shop-price">

                                ₱<?= number_format(
                                    (float)$product["price"],
                                    2
                                ) ?>

                            </div>

                        </div>


                        <!-- STOCK -->

                        <?php if ($stock > 10): ?>

                            <div class="shop-stock available">

                                <i class="bi bi-check-circle-fill"></i>

                                <?= $stock ?>
                                items left

                            </div>

                        <?php elseif ($stock > 0): ?>

                            <div class="shop-stock low">

                                <i class="bi bi-exclamation-circle-fill"></i>

                                Only <?= $stock ?>
                                left

                            </div>

                        <?php else: ?>

                            <div class="shop-stock out">

                                <i class="bi bi-x-circle-fill"></i>

                                Out of Stock

                            </div>

                        <?php endif; ?>


                        <!-- ADD TO CART -->

                        <?php if ($stock > 0): ?>

                            <form
                                method="POST"
                                action="add-to-cart.php"
                                class="shop-cart-form"
                            >

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= $productId ?>"
                                >

                                <button
                                    type="submit"
                                    class="shop-cart-button"
                                >

                                    <i class="bi bi-cart-plus"></i>

                                    Add to Cart

                                </button>

                            </form>

                        <?php else: ?>

                            <button
                                type="button"
                                class="shop-cart-button"
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


        <!-- EMPTY RESULT -->

        <div class="empty-shop">

            <i class="bi bi-search"></i>

            <h3>
                No products found
            </h3>

            <p>
                We couldn't find any products matching your search.
            </p>

            <a
                href="shop.php"
                class="clear-search"
            >
                View All Products
            </a>

        </div>

    <?php endif; ?>


</main>


<!-- =====================================================
     WISHLIST SCRIPT
===================================================== -->

<script>

document.querySelectorAll(".wishlist-button").forEach(button => {

    button.addEventListener("click", function () {

        const productId =
            this.dataset.productId;

        fetch("toggle-wishlist.php", {

            method: "POST",

            headers: {
                "Content-Type":
                    "application/x-www-form-urlencoded"
            },

            body:
                "product_id=" +
                encodeURIComponent(productId)

        })

        .then(response => response.json())

        .then(data => {

            if (data.logged_in === false) {

                window.location.href =
                    "login.php";

                return;
            }


            if (data.success) {

                const icon =
                    this.querySelector("i");


                if (data.in_wishlist) {

                    this.classList.add("active");

                    icon.className =
                        "bi bi-heart-fill";

                } else {

                    this.classList.remove("active");

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

    });

});

</script>


<!-- =========================================================
     FOOTER
========================================================= -->

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

                    <a href="#" aria-label="Facebook">
                        <i class="bi bi-facebook"></i>
                    </a>

                    <a href="#" aria-label="Instagram">
                        <i class="bi bi-instagram"></i>
                    </a>

                    <a href="#" aria-label="Twitter">
                        <i class="bi bi-twitter-x"></i>
                    </a>

                    <a href="#" aria-label="YouTube">
                        <i class="bi bi-youtube"></i>
                    </a>

                </div>

            </div>


            <!-- SHOP -->
            <div class="footer-column">

                <h3>Shop</h3>

                <a href="shop.php">All Categories</a>
                <a href="deals.php">Deals</a>
                <a href="new-arrivals.php">New Arrivals</a>
                <a href="shop.php">Best Sellers</a>

            </div>


            <!-- CUSTOMER SERVICES -->
            <div class="footer-column">

                <h3>Customer Services</h3>

                <a href="help.php">Help Center</a>
                <a href="track-order.php">Track Order</a>
                <a href="#">Returns & Refunds</a>
                <a href="#">Shipping Info</a>

            </div>


            <!-- ABOUT -->
            <div class="footer-column">

                <h3>About us</h3>

                <a href="#">About BrightBuy</a>
                <a href="#">Careers</a>
                <a href="#">Press & Media</a>
                <a href="#">Contact Us</a>

            </div>


            <!-- ACCOUNT -->
            <div class="footer-column">

                <h3>My Account</h3>

                <a href="my-orders.php">My Orders</a>
                <a href="wishlist.php">Wishlist</a>
                <a href="account.php">Account Settings</a>

            </div>


            <!-- APP -->
            <div class="footer-column app-column">

                <h3>Download Our App</h3>

                <p>
                    Get the app for better
                    shopping experience.
                </p>

                <div class="app-buttons">

                    <a href="#" class="app-button">
                        <i class="bi bi-apple"></i>
                        <span>
                            <small>Download on the</small>
                            App Store
                        </span>
                    </a>

                    <a href="#" class="app-button">
                        <i class="bi bi-google-play"></i>
                        <span>
                            <small>GET IT ON</small>
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

                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Refund Policy</a>

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
     BACK TO TOP
========================================================= -->

<button
    type="button"
    id="backToTop"
    aria-label="Back to top"
>
    <i class="bi bi-arrow-up"></i>
</button>


<!-- Bootstrap JS -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<!-- BrightBuy JS -->
<script src="../js/script.js"></script>


</body>

</html>