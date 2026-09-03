<?php

session_start();
require_once "../db.php";

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
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

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

</head>

<body>

<div class="container py-5">

    <!-- =================================================
         PAGE HEADER
    ================================================== -->

    <div class="text-center mb-5">

        <h1 class="fw-bold">
            <i class="bi bi-bag-check"></i>
            My Orders
        </h1>

        <p class="text-muted">
            View your previous BrightBuy orders.
        </p>

    </div>


    <?php if (!$orders): ?>

        <!-- =================================================
             EMPTY ORDERS
        ================================================== -->

        <div class="text-center py-5">

            <i
                class="bi bi-bag-x"
                style="font-size: 70px;"
            ></i>

            <h3 class="fw-bold mt-3">
                No Orders Yet
            </h3>

            <p class="text-muted">
                You haven't placed any orders yet.
            </p>

            <a
                href="../index.php"
                class="btn btn-primary px-4"
            >
                <i class="bi bi-shop"></i>
                Start Shopping
            </a>

        </div>

    <?php else: ?>


        <!-- =================================================
             ORDERS
        ================================================== -->

        <?php foreach ($orders as $order): ?>

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body p-4">


                    <!-- ORDER HEADER -->

                    <div
                        class="d-flex justify-content-between
                        align-items-center flex-wrap mb-3"
                    >

                        <div>

                            <h5 class="fw-bold mb-1">

    <a
        href="track-order.php?order_id=<?= (int)$order["order_id"] ?>"
        class="text-decoration-none"
    >
        Order #<?= (int)$order["order_id"] ?>
    </a>

</h5>

                            <small class="text-muted">

                                <?= date(
                                    "F d, Y h:i A",
                                    strtotime($order["created_at"])
                                ) ?>

                            </small>

                        </div>


                        <!-- STATUS -->

                        <span class="badge bg-warning text-dark">

                            <?= htmlspecialchars(
                                $order["status"]
                            ) ?>

                        </span>

                    </div>


                    <!-- ORDER ITEMS -->

                    <?php
                    $currentOrderItems =
                        $orderItems[$order["order_id"]] ?? [];
                    ?>

                    <?php foreach ($currentOrderItems as $item): ?>

                        <div
                            class="d-flex align-items-center
                            border-top py-3"
                        >

                            <img
                                src="../<?= htmlspecialchars(
                                    $item["image"]
                                ) ?>"
                                alt="<?= htmlspecialchars(
                                    $item["product_name"]
                                ) ?>"
                                width="70"
                                height="70"
                                style="object-fit: contain;"
                                class="me-3"
                            >


                            <div class="flex-grow-1">

                                <h6 class="fw-bold mb-1">

                                    <?= htmlspecialchars(
                                        $item["product_name"]
                                    ) ?>

                                </h6>

                                <small class="text-muted">

                                    ₱<?= number_format(
                                        $item["price"],
                                        2
                                    ) ?>

                                    ×

                                    <?= (int)$item["quantity"] ?>

                                </small>

                            </div>


                            <strong>

                                ₱<?= number_format(
                                    $item["subtotal"],
                                    2
                                ) ?>

                            </strong>

                        </div>

                    <?php endforeach; ?>


                    <!-- ORDER TOTAL -->

                    <div
                        class="d-flex justify-content-between
                        border-top pt-3 mt-2"
                    >

                        <span class="fw-bold">
                            Order Total
                        </span>

                        <span
                            class="fw-bold fs-5 text-primary"
                        >

                            ₱<?= number_format(
                                $order["total_amount"],
                                2
                            ) ?>

                        </span>

                    </div>

                    <div class="text-end mt-3">

    <a
        href="track-order.php?order_id=<?= (int)$order["order_id"] ?>"
        class="btn btn-outline-primary"
    >

        <i class="bi bi-box-seam"></i>

        Track Order

    </a>

</div>

                </div>

            </div>

        <?php endforeach; ?>


    <?php endif; ?>


    <!-- =================================================
         NAVIGATION<h5 class="fw-bold mb-1">
    ================================================== -->

    <div class="text-center mt-4">

        <a
            href="account.php"
            class="btn btn-outline-primary px-4 me-2"
        >
            <i class="bi bi-person"></i>
            My Account
        </a>

        <a
            href="../index.php"
            class="btn btn-primary px-4"
        >
            <i class="bi bi-house"></i>
            Back to Home
        </a>

    </div>

</div>

</body>

</html>