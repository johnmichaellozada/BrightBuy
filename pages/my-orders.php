<?php

session_start();
require_once "../db.php";

/* =====================================================
   LOGIN + CUSTOMER CHECK
===================================================== */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true ||
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "customer"
) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];


/* =====================================================
   GET USER ORDERS
   CANCELLED ORDERS ARE NOT SHOWN
===================================================== */

$orderStmt = $pdo->prepare("
    SELECT
        o.order_id,
        o.total_amount,
        o.gcash_number,
        o.gcash_reference,
        o.status,
        o.created_at
    FROM orders o
    WHERE o.user_id = ?
      AND LOWER(TRIM(o.status)) <> 'cancelled'
    ORDER BY o.created_at DESC
");

$orderStmt->execute([$user_id]);

$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   GET ORDER ITEMS
===================================================== */

$orderItems = [];

if ($orders) {

    $itemStmt = $pdo->prepare("
        SELECT
            oi.order_id,
            oi.product_id,
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

    foreach ($orders as $order) {

        $itemStmt->execute([
            $order["order_id"]
        ]);

        $orderItems[$order["order_id"]] =
            $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    }
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

    <title>My Orders - BrightBuy</title>


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


    <!-- Main CSS -->
    <link
        rel="stylesheet"
        href="../css/styles.css"
    >

</head>


<body class="my-orders-page">


<!-- =====================================================
     HEADER
===================================================== -->

<header class="orders-header">

    <div class="orders-header-content">

        <div class="orders-header-icon">
            <i class="bi bi-bag-check-fill"></i>
        </div>

        <div class="orders-header-text">

            <span class="orders-header-label">
                BRIGHTBUY
            </span>

            <h1>
                My Orders
            </h1>

            <p>
                View and track your BrightBuy orders
            </p>

        </div>

    </div>

</header>


<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="orders-container">


<?php if (!$orders): ?>


    <!-- =================================================
         EMPTY ORDERS
    ================================================== -->

    <section class="orders-empty">

        <div class="orders-empty-icon">
            <i class="bi bi-bag-x"></i>
        </div>

        <span class="orders-eyebrow">
            YOUR PURCHASES
        </span>

        <h2>
            No Orders Yet
        </h2>

        <p>
            You haven't placed any orders yet.
            Start shopping and your orders will appear here.
        </p>

        <a
            href="../index.php"
            class="orders-primary-btn"
        >
            <i class="bi bi-shop"></i>
            Start Shopping
        </a>

    </section>


<?php else: ?>


    <!-- =================================================
         ORDER HISTORY TITLE
    ================================================== -->

    <div class="orders-title-row">

        <div class="orders-title-text">

            <span class="orders-eyebrow">
                YOUR PURCHASES
            </span>

            <h2>
                Order History
            </h2>

            <p>
                Manage and track your recent purchases.
            </p>

        </div>


        <div class="orders-count">

            <div class="orders-count-icon">
                <i class="bi bi-bag-check"></i>
            </div>

            <div class="orders-count-text">

                <strong>
                    <?= count($orders) ?>
                </strong>

                <span>
                    <?= count($orders) === 1 ? "Order" : "Orders" ?>
                </span>

            </div>

        </div>

    </div>


    <!-- =================================================
         ORDERS
    ================================================== -->

    <div class="orders-list">


    <?php foreach ($orders as $order): ?>

        <?php

        $status = strtolower(
            trim($order["status"] ?? "pending")
        );

        $statusClass = "status-pending";
        $statusIcon = "bi-clock-fill";

        if ($status === "processing") {

            $statusClass = "status-processing";
            $statusIcon = "bi-arrow-repeat";

        } elseif ($status === "shipped") {

            $statusClass = "status-shipped";
            $statusIcon = "bi-truck";

        } elseif ($status === "delivered") {

            $statusClass = "status-delivered";
            $statusIcon = "bi-check-circle-fill";
        }


        $currentOrderItems =
            $orderItems[$order["order_id"]] ?? [];


        $isGcash =
            !empty($order["gcash_number"]) ||
            !empty($order["gcash_reference"]);

        ?>


        <!-- =================================================
             ORDER CARD
        ================================================== -->

        <article class="order-card">


            <!-- =============================================
                 ORDER HEADER
            ============================================== -->

            <div class="order-card-header">

                <div class="order-information">

                    <a
                        href="track-order.php?order_id=<?= (int)$order["order_id"] ?>"
                        class="order-number"
                    >
                        Order #<?= (int)$order["order_id"] ?>
                    </a>

                    <div class="order-date">

                        <i class="bi bi-calendar3"></i>

                        <span>
                            <?= date(
                                "F d, Y h:i A",
                                strtotime($order["created_at"])
                            ) ?>
                        </span>

                    </div>

                </div>


                <!-- STATUS -->

                <div class="order-status <?= $statusClass ?>">

                    <i class="bi <?= $statusIcon ?>"></i>

                    <span>
                        <?= htmlspecialchars(
                            ucfirst($status)
                        ) ?>
                    </span>

                </div>

            </div>


            <!-- =============================================
                 PRODUCTS
            ============================================== -->

            <div class="order-products">

                <div class="order-products-title">

                    <div class="section-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>

                    <div class="section-title-text">

                        <strong>
                            Products
                        </strong>

                        <span>
                            <?= count($currentOrderItems) ?>
                            <?= count($currentOrderItems) === 1
                                ? "item"
                                : "items" ?>
                        </span>

                    </div>

                </div>


                <?php if ($currentOrderItems): ?>


                    <?php foreach ($currentOrderItems as $item): ?>

                        <div class="order-product">


                            <!-- PRODUCT IMAGE -->

                            <div class="order-product-image">

                                <?php if (!empty($item["image"])): ?>

                                    <img
                                        src="../<?= htmlspecialchars($item["image"]) ?>"
                                        alt="<?= htmlspecialchars($item["product_name"]) ?>"
                                    >

                                <?php else: ?>

                                    <div class="product-placeholder">
                                        <i class="bi bi-image"></i>
                                    </div>

                                <?php endif; ?>

                            </div>


                            <!-- PRODUCT INFO -->

                            <div class="order-product-info">

                                <h3>
                                    <?= htmlspecialchars(
                                        $item["product_name"]
                                    ) ?>
                                </h3>

                                <div class="product-price">

                                    ₱<?= number_format(
                                        $item["price"],
                                        2
                                    ) ?>

                                    <span>
                                        ×
                                    </span>

                                    <?= (int)$item["quantity"] ?>

                                </div>

                            </div>


                            <!-- SUBTOTAL -->

                            <div class="order-product-subtotal">

                                <span>
                                    Subtotal
                                </span>

                                <strong>
                                    ₱<?= number_format(
                                        $item["subtotal"],
                                        2
                                    ) ?>
                                </strong>

                            </div>

                        </div>

                    <?php endforeach; ?>


                <?php else: ?>

                    <div class="order-no-items">

                        <i class="bi bi-box"></i>

                        <span>
                            No product information available.
                        </span>

                    </div>

                <?php endif; ?>

            </div>


            <!-- =============================================
                 PAYMENT
            ============================================== -->

            <div class="order-payment-section">


                <div class="payment-section-heading">

                    <div class="payment-icon">
                        <i class="bi bi-wallet2"></i>
                    </div>

                    <div class="payment-heading-text">

                        <span class="payment-eyebrow">
                            PAYMENT
                        </span>

                        <h3>
                            <?= $isGcash
                                ? "GCash Payment"
                                : "Cash on Delivery" ?>
                        </h3>

                    </div>


                    <div class="payment-method-badge <?= $isGcash
                        ? "gcash-badge"
                        : "cod-badge" ?>">

                        <i class="bi <?= $isGcash
                            ? "bi-phone-fill"
                            : "bi-cash-stack" ?>"></i>

                        <?= $isGcash
                            ? "GCash"
                            : "COD" ?>

                    </div>

                </div>


                <?php if ($isGcash): ?>


                    <!-- GCASH -->

                    <div class="order-gcash-information">

                        <div class="gcash-card-top">

                            <div class="gcash-brand">

                                <div class="gcash-brand-icon">
                                    <i class="bi bi-phone-fill"></i>
                                </div>

                                <div>

                                    <strong>
                                        GCash
                                    </strong>

                                    <span>
                                        Payment details
                                    </span>

                                </div>

                            </div>

                            <div class="gcash-verified">

                                <i class="bi bi-shield-check"></i>

                                Payment Submitted

                            </div>

                        </div>


                        <div class="gcash-details-grid">


                            <?php if (!empty($order["gcash_number"])): ?>

                                <div class="order-gcash-row">

                                    <div class="gcash-row-icon">
                                        <i class="bi bi-phone"></i>
                                    </div>

                                    <div class="gcash-row-content">

                                        <span>
                                            GCash Number
                                        </span>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $order["gcash_number"]
                                            ) ?>
                                        </strong>

                                    </div>

                                    <button
                                        type="button"
                                        class="gcash-copy-btn"
                                        data-copy="<?= htmlspecialchars(
                                            $order["gcash_number"],
                                            ENT_QUOTES
                                        ) ?>"
                                        title="Copy GCash number"
                                    >
                                        <i class="bi bi-copy"></i>
                                    </button>

                                </div>

                            <?php endif; ?>


                            <?php if (!empty($order["gcash_reference"])): ?>

                                <div class="order-gcash-row">

                                    <div class="gcash-row-icon reference-icon">
                                        <i class="bi bi-receipt"></i>
                                    </div>

                                    <div class="gcash-row-content">

                                        <span>
                                            Reference Number
                                        </span>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $order["gcash_reference"]
                                            ) ?>
                                        </strong>

                                    </div>

                                    <button
                                        type="button"
                                        class="gcash-copy-btn"
                                        data-copy="<?= htmlspecialchars(
                                            $order["gcash_reference"],
                                            ENT_QUOTES
                                        ) ?>"
                                        title="Copy reference number"
                                    >
                                        <i class="bi bi-copy"></i>
                                    </button>

                                </div>

                            <?php endif; ?>


                        </div>

                    </div>


                <?php else: ?>


                    <!-- COD -->

                    <div class="cod-payment-information">

                        <div class="cod-payment-icon">
                            <i class="bi bi-cash-stack"></i>
                        </div>

                        <div>

                            <strong>
                                Cash on Delivery
                            </strong>

                            <span>
                                Payment will be collected when your order is delivered.
                            </span>

                        </div>

                    </div>


                <?php endif; ?>


            </div>


            <!-- =============================================
                 FOOTER
            ============================================== -->

            <div class="order-card-footer">


                <div class="order-total">

                    <span>
                        Order Total
                    </span>

                    <strong>
                        ₱<?= number_format(
                            $order["total_amount"],
                            2
                        ) ?>
                    </strong>

                </div>


                <div class="order-action-buttons">


                    <!-- TRACK -->

                    <a
                        href="track-order.php?order_id=<?= (int)$order["order_id"] ?>"
                        class="track-order-btn"
                    >
                        <i class="bi bi-eye"></i>
                        Track Order
                    </a>


                    <!-- CANCEL -->

                    <?php if ($status === "pending"): ?>

                        <form
                            method="POST"
                            action="../actions/cancel-order.php"
                            onsubmit="return confirm('Are you sure you want to cancel this order?');"
                        >

                            <input
                                type="hidden"
                                name="order_id"
                                value="<?= (int)$order["order_id"] ?>"
                            >

                            <button
                                type="submit"
                                class="cancel-order-btn"
                            >
                                <i class="bi bi-x-circle"></i>
                                Cancel Order
                            </button>

                        </form>

                    <?php endif; ?>


                </div>

            </div>


        </article>


    <?php endforeach; ?>

    </div>


<?php endif; ?>


<!-- =====================================================
     NAVIGATION
===================================================== -->

<div class="orders-navigation">

    <a
        href="account.php"
        class="orders-outline-btn"
    >
        <i class="bi bi-person"></i>
        My Account
    </a>

    <a
        href="../index.php"
        class="orders-primary-btn"
    >
        <i class="bi bi-house"></i>
        Back to BrightBuy
    </a>

</div>


</main>


<!-- =====================================================
     COPY GCASH
===================================================== -->

<script>

document.querySelectorAll(".gcash-copy-btn").forEach(function(button) {

    button.addEventListener("click", function() {

        const value = this.dataset.copy;

        if (!value) {
            return;
        }

        navigator.clipboard.writeText(value)
            .then(() => {

                const original = this.innerHTML;

                this.innerHTML =
                    '<i class="bi bi-check2"></i>';

                this.classList.add("copied");

                setTimeout(() => {

                    this.innerHTML = original;

                    this.classList.remove("copied");

                }, 1400);

            })
            .catch(() => {

                alert(
                    "Unable to copy this information."
                );

            });

    });

});

</script>


</body>
</html>