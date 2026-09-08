<?php

session_start();
require_once "../db.php";

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
===================================================== */

$orderStmt = $pdo->prepare("
    SELECT
        o.order_id,
        o.total_amount,
        o.status,
        o.created_at
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");

$orderStmt->execute([$user_id]);

$orders = $orderStmt->fetchAll();


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
            $itemStmt->fetchAll();
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

    <!-- BrightBuy Main CSS -->
    <link
        rel="stylesheet"
        href="../css/styles.css"
    >

</head>

<body class="my-orders-page">


<!-- =====================================================
     PAGE HEADER
===================================================== -->

<section class="orders-header">

    <div class="orders-header-content">

        <div class="orders-icon">

            <i class="bi bi-bag-check-fill"></i>

        </div>

        <h1>
            My Orders
        </h1>

        <p>
            View and track your BrightBuy orders
        </p>

    </div>

</section>


<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="orders-container">

    <?php if (!$orders): ?>

        <!-- =================================================
             EMPTY ORDERS
        ================================================== -->

        <div class="orders-empty">

            <div class="orders-empty-icon">

                <i class="bi bi-bag-x"></i>

            </div>

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

        </div>


    <?php else: ?>


        <!-- =================================================
             ORDER SUMMARY
        ================================================== -->

        <div class="orders-title-row">

            <div>

                <span class="orders-eyebrow">
                    YOUR PURCHASES
                </span>

                <h2>
                    Order History
                </h2>

            </div>

            <div class="orders-count">

                <i class="bi bi-bag-check"></i>

                <?= count($orders) ?>

                <?= count($orders) === 1 ? "Order" : "Orders" ?>

            </div>

        </div>


        <!-- =================================================
             ORDERS
        ================================================== -->

        <?php foreach ($orders as $order): ?>

            <?php

            $status = strtolower(
                trim($order["status"])
            );

            $statusClass = "status-pending";

            if ($status === "processing") {
                $statusClass = "status-processing";
            } elseif ($status === "shipped") {
                $statusClass = "status-shipped";
            } elseif ($status === "delivered") {
                $statusClass = "status-delivered";
            } elseif ($status === "cancelled") {
                $statusClass = "status-cancelled";
            }

            $currentOrderItems =
                $orderItems[$order["order_id"]] ?? [];

            ?>

            <article class="order-card">


                <!-- =================================================
                     ORDER TOP
                ================================================== -->

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

                            <?= date(
                                "F d, Y h:i A",
                                strtotime($order["created_at"])
                            ) ?>

                        </div>

                    </div>


                    <div class="order-status <?= $statusClass ?>">

                        <?php if ($status === "pending"): ?>

                            <i class="bi bi-clock-fill"></i>

                        <?php elseif ($status === "processing"): ?>

                            <i class="bi bi-arrow-repeat"></i>

                        <?php elseif ($status === "shipped"): ?>

                            <i class="bi bi-truck"></i>

                        <?php elseif ($status === "delivered"): ?>

                            <i class="bi bi-check-circle-fill"></i>

                        <?php elseif ($status === "cancelled"): ?>

                            <i class="bi bi-x-circle-fill"></i>

                        <?php else: ?>

                            <i class="bi bi-info-circle-fill"></i>

                        <?php endif; ?>

                        <?= htmlspecialchars($order["status"]) ?>

                    </div>

                </div>


                <!-- =================================================
                     PRODUCTS
                ================================================== -->

                <div class="order-products">

                    <div class="order-products-title">

                        <i class="bi bi-box-seam"></i>

                        Products

                    </div>


                    <?php foreach ($currentOrderItems as $item): ?>

                        <div class="order-product">

                            <div class="order-product-image">

                                <?php if (!empty($item["image"])): ?>

                                    <img
                                        src="../<?= htmlspecialchars($item["image"]) ?>"
                                        alt="<?= htmlspecialchars($item["product_name"]) ?>"
                                    >

                                <?php else: ?>

                                    <img
                                        src="../images/placeholder.png"
                                        alt="Product image"
                                    >

                                <?php endif; ?>

                            </div>


                            <div class="order-product-info">

                                <h3>
                                    <?= htmlspecialchars(
                                        $item["product_name"]
                                    ) ?>
                                </h3>

                                <p>

                                    ₱<?= number_format(
                                        $item["price"],
                                        2
                                    ) ?>

                                    ×

                                    <?= (int)$item["quantity"] ?>

                                </p>

                            </div>


                            <div class="order-product-subtotal">

                                ₱<?= number_format(
                                    $item["subtotal"],
                                    2
                                ) ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>


                <!-- =================================================
                     ORDER BOTTOM
                ================================================== -->

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

    <a
        href="track-order.php?order_id=<?= (int)$order["order_id"] ?>"
        class="track-order-btn"
    >
        <i class="bi bi-eye"></i>
        Track Order
    </a>


    <?php if ($order["status"] === "Pending"): ?>

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


    <?php endif; ?>


    <!-- =================================================
         NAVIGATION
    ================================================== -->

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


</body>

</html>