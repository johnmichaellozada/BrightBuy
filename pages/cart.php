<?php

/**
 * BrightBuy Customer Cart
 *
 * Displays the customer's cart.
 *
 * CRUD operations are processed by:
 *
 * ../actions/cart.php
 */

session_start();

require_once "../db.php";


/* =========================================================
   CUSTOMER LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}


/* =========================================================
   GET CUSTOMER ID
========================================================= */

$user_id = (int) ($_SESSION["user_id"] ?? 0);

if ($user_id <= 0) {
    header("Location: login.php");
    exit;
}


/* =========================================================
   GET CUSTOMER CART
========================================================= */

$cartStmt = $pdo->prepare("
    SELECT cart_id
    FROM cart
    WHERE user_id = ?
    LIMIT 1
");

$cartStmt->execute([
    $user_id
]);

$cart = $cartStmt->fetch();


/* =========================================================
   DEFAULT VALUES
========================================================= */

$items = [];
$total = 0;


/* =========================================================
   GET CART ITEMS
========================================================= */

if ($cart) {

    $cart_id = (int) $cart["cart_id"];

    $itemsStmt = $pdo->prepare("
        SELECT
            ci.cart_item_id,
            ci.product_id,
            ci.quantity,

            p.product_name,
            p.price,
            p.image,
            p.stock

        FROM cart_items ci

        INNER JOIN products p
            ON ci.product_id = p.product_id

        WHERE ci.cart_id = ?

        ORDER BY ci.cart_item_id DESC
    ");

    $itemsStmt->execute([
        $cart_id
    ]);

    $items = $itemsStmt->fetchAll();


    /* =====================================================
       CALCULATE TOTAL
    ===================================================== */

    foreach ($items as $item) {

        $total +=
            (float) $item["price"] *
            (int) $item["quantity"];
    }
}


/* =========================================================
   MESSAGES
========================================================= */

$success = $_GET["success"] ?? "";
$error = $_GET["error"] ?? "";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>BrightBuy | Shopping Cart</title>


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


    <!-- BrightBuy Main CSS -->
    <link
        rel="stylesheet"
        href="../css/styles.css"
    >

</head>


<body class="cart-page">


<!-- =========================================================
     CART PAGE WRAPPER
========================================================= -->

<div class="cart-page-wrapper">


    <!-- =====================================================
         TOP HEADER
    ====================================================== -->

    <section class="cart-header">

        <div class="cart-header-inner">

            <div class="cart-title-area">

                <div class="cart-title-icon">
                    <i class="bi bi-cart3"></i>
                </div>

                <div>

                    <span class="cart-eyebrow">
                        BRIGHTBUY
                    </span>

                    <h1>
                        My Cart
                    </h1>

                    <p>
                        Review and manage your items.
                    </p>

                </div>

            </div>


            <a
    href="shop.php"
    class="cart-yellow-btn"
>

                <i class="bi bi-arrow-left"></i>

                Continue Shopping

            </a>

        </div>

    </section>



    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="cart-main">


        <!-- =================================================
             SUCCESS / ERROR MESSAGES
        ================================================== -->

        <?php if ($success === "added"): ?>

            <div class="cart-alert cart-alert-success">

                <i class="bi bi-check-circle-fill"></i>

                <span>
                    Product added to your cart.
                </span>

            </div>

        <?php endif; ?>


        <?php if ($success === "updated"): ?>

            <div class="cart-alert cart-alert-success">

                <i class="bi bi-check-circle-fill"></i>

                <span>
                    Cart quantity updated.
                </span>

            </div>

        <?php endif; ?>


        <?php if ($success === "removed"): ?>

            <div class="cart-alert cart-alert-success">

                <i class="bi bi-check-circle-fill"></i>

                <span>
                    Product removed from your cart.
                </span>

            </div>

        <?php endif; ?>


        <?php if ($success === "cleared"): ?>

            <div class="cart-alert cart-alert-success">

                <i class="bi bi-check-circle-fill"></i>

                <span>
                    Your cart has been cleared.
                </span>

            </div>

        <?php endif; ?>


        <?php if ($error === "item_not_found"): ?>

            <div class="cart-alert cart-alert-danger">

                <i class="bi bi-exclamation-circle-fill"></i>

                <span>
                    Cart item could not be found.
                </span>

            </div>

        <?php endif; ?>


        <?php if ($error === "out_of_stock"): ?>

            <div class="cart-alert cart-alert-danger">

                <i class="bi bi-exclamation-triangle-fill"></i>

                <span>
                    This product is currently out of stock.
                </span>

            </div>

        <?php endif; ?>



        <?php if (empty($items)): ?>


            <!-- =================================================
                 EMPTY CART
            ================================================== -->

            <section class="cart-empty">

                <div class="cart-empty-icon">

                    <i class="bi bi-cart-x"></i>

                </div>

                <span class="cart-empty-eyebrow">
                    YOUR SHOPPING CART
                </span>

                <h2>
                    Your Cart is Empty
                </h2>

                <p>
                    You haven't added any products yet.
                    Find something you love and add it to your cart.
                </p>

                <a
                    href="shop.php"
                    class="cart-primary-btn"
                >

                    <i class="bi bi-bag"></i>

                    Start Shopping

                </a>

            </section>



        <?php else: ?>


            <!-- =================================================
                 CART CONTENT HEADER
            ================================================== -->

            <div class="cart-content-heading">

                <div>

                    <span>
                        YOUR SHOPPING CART
                    </span>

                    <h2>
                        Cart Items
                    </h2>

                </div>

                <div class="cart-item-count">

                    <i class="bi bi-bag-check"></i>

                    <strong>
                        <?= count($items) ?>
                    </strong>

                    <small>
                        <?= count($items) === 1 ? "Item" : "Items" ?>
                    </small>

                </div>

            </div>



            <!-- =================================================
                 CART LAYOUT
            ================================================== -->

            <div class="cart-layout">


                <!-- =============================================
                     CART ITEMS
                ============================================== -->

                <section class="cart-items-column">


                    <?php foreach ($items as $item): ?>

                        <?php

                        $itemSubtotal =
                            (float) $item["price"] *
                            (int) $item["quantity"];

                        ?>


                        <article class="cart-product-card">


                            <!-- PRODUCT IMAGE -->

                            <div class="cart-product-image">

                                <?php if (!empty($item["image"])): ?>

                                    <img
                                        src="../<?= htmlspecialchars($item["image"]) ?>"
                                        alt="<?= htmlspecialchars($item["product_name"]) ?>"
                                    >

                                <?php else: ?>

                                    <i class="bi bi-image"></i>

                                <?php endif; ?>

                            </div>



                            <!-- PRODUCT DETAILS -->

                            <div class="cart-product-details">

                                <span class="cart-product-label">
                                    PRODUCT
                                </span>

                                <h3>
                                    <?= htmlspecialchars(
                                        $item["product_name"]
                                    ) ?>
                                </h3>

                                <div class="cart-product-price">

                                    ₱<?= number_format(
                                        (float) $item["price"],
                                        2
                                    ) ?>

                                </div>

                                <div class="cart-product-stock">

                                    <i class="bi bi-box-seam"></i>

                                    Stock:
                                    <?= (int) $item["stock"] ?>

                                </div>

                            </div>



                            <!-- QUANTITY -->

                            <div class="cart-quantity-area">

                                <form
                                    method="POST"
                                    action="../actions/cart.php"
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="update"
                                    >

                                    <input
                                        type="hidden"
                                        name="cart_item_id"
                                        value="<?= (int) $item["cart_item_id"] ?>"
                                    >

                                    <label>
                                        Quantity
                                    </label>

                                    <div class="cart-quantity-control">

                                        <input
                                            type="number"
                                            name="quantity"
                                            min="1"
                                            max="<?= (int) $item["stock"] ?>"
                                            value="<?= (int) $item["quantity"] ?>"
                                            required
                                        >

                                        <button
                                            type="submit"
                                            title="Update Quantity"
                                        >

                                            <i class="bi bi-arrow-repeat"></i>

                                        </button>

                                    </div>

                                </form>

                            </div>



                            <!-- SUBTOTAL / REMOVE -->

                            <div class="cart-product-total">

                                <span>
                                    Subtotal
                                </span>

                                <strong>

                                    ₱<?= number_format(
                                        $itemSubtotal,
                                        2
                                    ) ?>

                                </strong>


                                <form
                                    method="POST"
                                    action="../actions/cart.php"
                                    onsubmit="
                                        return confirm(
                                            'Remove this product from your cart?'
                                        );
                                    "
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="remove"
                                    >

                                    <input
                                        type="hidden"
                                        name="cart_item_id"
                                        value="<?= (int) $item["cart_item_id"] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="cart-remove-btn"
                                    >

                                        <i class="bi bi-trash3"></i>

                                        Remove

                                    </button>

                                </form>

                            </div>

                        </article>

                    <?php endforeach; ?>



                    <!-- =========================================
                         CLEAR CART
                    ========================================== -->

                    <div class="cart-clear-row">

                        <form
                            method="POST"
                            action="../actions/cart.php"
                            onsubmit="
                                return confirm(
                                    'Are you sure you want to clear your cart?'
                                );
                            "
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="clear"
                            >

                            <button
    type="submit"
    class="cart-yellow-btn"
>
                                <i class="bi bi-trash3"></i>

                                Clear Cart

                            </button>

                        </form>

                    </div>


                </section>



                <!-- =============================================
                     ORDER SUMMARY
                ============================================== -->

                <aside class="cart-summary-card">


                    <div class="cart-summary-header">

                        <div class="cart-summary-icon">

                            <i class="bi bi-receipt"></i>

                        </div>

                        <div>

                            <span>
                                ORDER SUMMARY
                            </span>

                            <h2>
                                Your Order
                            </h2>

                        </div>

                    </div>



                    <div class="cart-summary-body">


                        <div class="cart-summary-row">

                            <span>
                                Products
                            </span>

                            <strong>
                                <?= count($items) ?>
                            </strong>

                        </div>



                        <div class="cart-summary-divider"></div>



                        <div class="cart-summary-total">

                            <span>
                                Total
                            </span>

                            <strong>

                                ₱<?= number_format(
                                    $total,
                                    2
                                ) ?>

                            </strong>

                        </div>



                        <a
                            href="checkout.php"
                            class="cart-checkout-btn"
                        >

                            <i class="bi bi-credit-card-fill"></i>

                            Proceed to Checkout

                        </a>



                        <div class="cart-secure-note">

                            <i class="bi bi-shield-check"></i>

                            <div>

                                <strong>
                                    Secure Checkout
                                </strong>

                                <span>
                                    Your information is protected.
                                </span>

                            </div>

                        </div>

                    </div>

                </aside>


            </div>


        <?php endif; ?>


        <!-- =================================================
             BACK TO HOME
        ================================================== -->

        <div class="cart-back-home">

            <a
                href="../index.php"
                class="cart-back-btn"
            >

                <i class="bi bi-house"></i>

                Back to BrightBuy

            </a>

        </div>


    </main>

</div>


</body>
</html>