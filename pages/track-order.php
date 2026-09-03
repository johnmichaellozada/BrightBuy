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
   GET ORDER ID
===================================================== */

$order_id = filter_input(
    INPUT_GET,
    "order_id",
    FILTER_VALIDATE_INT
);


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
   STATUS
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

    <title>Track Order - BrightBuy</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>

        body {
            background: #f5f7ff;
            font-family: Arial, sans-serif;
        }

        .tracking-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .tracking-icon {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            background: #e9f0ff;
            color: #073b9d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: auto;
        }

        .tracking-step {
            text-align: center;
            position: relative;
        }

        .tracking-step.active .tracking-icon {
            background: #073b9d;
            color: white;
        }

        .tracking-step.completed .tracking-icon {
            background: #198754;
            color: white;
        }

        .tracking-line {
            height: 4px;
            background: #dee2e6;
            flex: 1;
            margin: 0 10px;
            margin-top: -32px;
        }

        .tracking-line.completed {
            background: #198754;
        }

        .product-image {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }

    </style>

</head>

<body>

<div class="container py-5">

    <!-- =================================================
         PAGE HEADER
    ================================================== -->

    <div class="text-center mb-5">

        <h1 class="fw-bold">

            <i class="bi bi-box-seam"></i>

            Track My Order

        </h1>

        <p class="text-muted">
            Check the current status of your BrightBuy order.
        </p>

    </div>


    <?php if (!$order): ?>

        <!-- =================================================
             NO ORDER SELECTED
        ================================================== -->

        <div class="card tracking-card">

            <div class="card-body text-center p-5">

                <i
                    class="bi bi-search"
                    style="font-size: 60px;"
                ></i>

                <h3 class="fw-bold mt-3">
                    Select an Order
                </h3>

                <p class="text-muted">
                    Go to My Orders and select an order to track.
                </p>

                <a
                    href="my-orders.php"
                    class="btn btn-primary px-4"
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

        <div class="card tracking-card mb-4">

            <div class="card-body p-4">

                <div
                    class="d-flex justify-content-between
                    align-items-center flex-wrap"
                >

                    <div>

                        <h4 class="fw-bold mb-1">

                            Order #<?= (int)$order["order_id"] ?>

                        </h4>

                        <small class="text-muted">

                            <?= date(
                                "F d, Y h:i A",
                                strtotime($order["created_at"])
                            ) ?>

                        </small>

                    </div>


                    <span class="badge bg-warning text-dark">

                        <?= htmlspecialchars(
                            $order["status"]
                        ) ?>

                    </span>

                </div>

            </div>

        </div>


        <!-- =================================================
             ORDER STATUS
        ================================================== -->

        <div class="card tracking-card mb-4">

            <div class="card-body p-5">

                <h4 class="fw-bold text-center mb-5">
                    Order Status
                </h4>


                <div class="d-flex align-items-start">


                    <!-- Pending -->

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

                        <h6 class="fw-bold mt-3">
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


                    <!-- Processing -->

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

                        <h6 class="fw-bold mt-3">
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


                    <!-- Shipped -->

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

                        <h6 class="fw-bold mt-3">
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


                    <!-- Delivered -->

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

                        <h6 class="fw-bold mt-3">
                            Delivered
                        </h6>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             PRODUCTS
        ================================================== -->

        <div class="card tracking-card mb-4">

            <div class="card-body p-4">

                <h4 class="fw-bold mb-4">
                    Ordered Items
                </h4>


                <?php foreach ($orderItems as $item): ?>

                    <div
                        class="d-flex align-items-center
                        border-bottom py-3"
                    >

                        <img
                            src="../<?= htmlspecialchars(
                                $item["image"]
                            ) ?>"
                            alt="<?= htmlspecialchars(
                                $item["product_name"]
                            ) ?>"
                            class="product-image me-3"
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


                <div
                    class="d-flex justify-content-between
                    pt-3"
                >

                    <strong>
                        Order Total
                    </strong>

                    <strong class="text-primary fs-5">

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

        <div class="card tracking-card mb-4">

            <div class="card-body p-4">

                <h4 class="fw-bold mb-3">

                    <i class="bi bi-geo-alt"></i>

                    Delivery Address

                </h4>


                <p class="mb-1 fw-bold">

                    <?= htmlspecialchars(
                        $order["recipient_name"] ?? ""
                    ) ?>

                </p>


                <p class="mb-1">

                    <?= htmlspecialchars(
                        $order["phone"] ?? ""
                    ) ?>

                </p>


                <p class="mb-1">

                    <?= htmlspecialchars(
                        $order["address_line"] ?? ""
                    ) ?>

                </p>


                <p class="mb-1">

                    <?= htmlspecialchars(
                        $order["barangay"] ?? ""
                    ) ?>,

                    <?= htmlspecialchars(
                        $order["city"] ?? ""
                    ) ?>

                </p>


                <p class="mb-0">

                    <?= htmlspecialchars(
                        $order["province"] ?? ""
                    ) ?>

                    <?php if (!empty($order["postal_code"])): ?>

                        <?= htmlspecialchars(
                            $order["postal_code"]
                        ) ?>

                    <?php endif; ?>

                </p>

            </div>

        </div>


    <?php endif; ?>


    <!-- =================================================
         NAVIGATION
    ================================================== -->

    <div class="text-center mt-4">

        <a
            href="my-orders.php"
            class="btn btn-outline-primary px-4 me-2"
        >

            <i class="bi bi-bag-check"></i>

            My Orders

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