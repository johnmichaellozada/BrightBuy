<?php

session_start();
require_once "../db.php";


/* =========================================================
   ADMIN ACCESS CHECK
========================================================= */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: admin-login.php");
    exit;
}


$user_id = $_SESSION["user_id"];


/* =========================================================
   CHECK ADMIN ROLE
========================================================= */

$userStmt = $pdo->prepare("
    SELECT role
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$userStmt->execute([
    $user_id
]);

$currentUser = $userStmt->fetch();


if (
    !$currentUser ||
    $currentUser["role"] !== "admin"
) {
    die("Access denied. Administrator privileges required.");
}


/* =========================================================
   GET CUSTOMER ID
========================================================= */

$customer_id = filter_input(
    INPUT_GET,
    "user_id",
    FILTER_VALIDATE_INT
);


if (!$customer_id) {
    header("Location: admin-dashboard.php");
    exit;
}


/* =========================================================
   GET CUSTOMER INFORMATION
========================================================= */

$customerStmt = $pdo->prepare("
    SELECT
        user_id,
        first_name,
        last_name,
        email,
        phone,
        created_at
    FROM users
    WHERE user_id = ?
      AND role = 'customer'
    LIMIT 1
");

$customerStmt->execute([
    $customer_id
]);

$customer = $customerStmt->fetch();


if (!$customer) {
    die("Customer not found.");
}


/* =========================================================
   GET CUSTOMER ORDERS + PAYMENT INFORMATION
========================================================= */

$orderStmt = $pdo->prepare("
    SELECT
        o.order_id,
        o.total_amount,
        o.status,
        o.created_at,

        o.gcash_number,
        o.gcash_reference,

        pay.payment_method,
        pay.amount AS payment_amount,
        pay.payment_status,
        pay.transaction_reference,
        pay.paid_at

    FROM orders o

    LEFT JOIN payments pay
        ON pay.order_id = o.order_id

    WHERE o.user_id = ?

    ORDER BY o.created_at DESC
");

$orderStmt->execute([
    $customer_id
]);

$orders = $orderStmt->fetchAll();


/* =========================================================
   GET ORDER ITEMS
========================================================= */

$orderItems = [];


if (!empty($orders)) {

    $orderIds = array_column(
        $orders,
        "order_id"
    );


    $placeholders = implode(
        ",",
        array_fill(
            0,
            count($orderIds),
            "?"
        )
    );


    $itemsStmt = $pdo->prepare("
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

        WHERE oi.order_id IN ($placeholders)

        ORDER BY oi.order_item_id ASC
    ");


    $itemsStmt->execute(
        $orderIds
    );


    while ($item = $itemsStmt->fetch()) {

        $orderItems[
            $item["order_id"]
        ][] = $item;
    }
}


/* =========================================================
   PAYMENT DISPLAY HELPER
========================================================= */

function getPaymentMethod($order)
{
    if (
        isset($order["payment_method"]) &&
        !empty($order["payment_method"])
    ) {

        return strtolower(
            trim($order["payment_method"])
        );
    }


    if (
        !empty($order["gcash_number"]) ||
        !empty($order["gcash_reference"])
    ) {

        return "gcash";
    }


    return "cod";
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
        Customer Orders | BrightBuy Admin
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
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


<body class="admin-orders-page">


<!-- =========================================================
     ADMIN HEADER
========================================================= -->

<header class="admin-header">

    <div class="admin-brand">

        <i class="bi bi-shield-lock-fill"></i>

        <div>

            <h4>
                BrightBuy Admin
            </h4>

            <small>
                Customer Orders
            </small>

        </div>

    </div>


    <div class="admin-actions">

        <a
            href="admin-products.php"
            class="btn btn-warning btn-sm fw-semibold"
        >

            <i class="bi bi-box-seam"></i>

            Inventory

        </a>


        <a
            href="logout.php"
            class="btn btn-outline-light btn-sm"
        >

            <i class="bi bi-box-arrow-right"></i>

            Logout

        </a>

    </div>

</header>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main class="orders-container">


    <!-- =====================================================
         BACK BUTTON
    ====================================================== -->

    <a
        href="admin-dashboard.php"
        class="back-link"
    >

        <i class="bi bi-arrow-left"></i>

        Back to Customers

    </a>


    <!-- =====================================================
         CUSTOMER INFORMATION
    ====================================================== -->

    <div class="customer-card">

        <div class="customer-profile">

            <div class="customer-avatar">

                <?= strtoupper(
                    substr(
                        $customer["first_name"],
                        0,
                        1
                    )
                ) ?>

            </div>


            <div>

                <h2 class="customer-name">

                    <?= htmlspecialchars(
                        $customer["first_name"]
                        . " "
                        . $customer["last_name"]
                    ) ?>

                </h2>


                <p class="customer-email">

                    <i class="bi bi-envelope"></i>

                    <?= htmlspecialchars(
                        $customer["email"]
                    ) ?>

                </p>


                <?php if (!empty($customer["phone"])): ?>

                    <p class="customer-phone">

                        <i class="bi bi-telephone"></i>

                        <?= htmlspecialchars(
                            $customer["phone"]
                        ) ?>

                    </p>

                <?php endif; ?>

            </div>

        </div>

    </div>


    <!-- =====================================================
         ORDERS TITLE
    ====================================================== -->

    <div class="orders-title">

        <h3>

            <i class="bi bi-bag-check-fill"></i>

            Orders

        </h3>


        <span class="orders-count">

            <?= count($orders) ?>

            <?= count($orders) === 1
                ? "Order"
                : "Orders"
            ?>

        </span>

    </div>


    <!-- =====================================================
         NO ORDERS
    ====================================================== -->

    <?php if (empty($orders)): ?>

        <div class="empty-orders">

            <i class="bi bi-bag-x"></i>

            <h5>
                No Orders Yet
            </h5>

            <p>
                This customer has not placed any orders.
            </p>

        </div>


    <?php else: ?>


        <!-- =================================================
             ORDER LIST
        ================================================== -->

        <?php foreach ($orders as $order): ?>


            <?php

                /* -----------------------------------------
                   NORMALIZE STATUS
                ----------------------------------------- */

                $currentStatus = trim(
                    $order["status"] ?? ""
                );


                /* -----------------------------------------
                   CHECK IF ORDER IS COMPLETED
                ----------------------------------------- */

                $isDelivered =
                    strcasecmp(
                        $currentStatus,
                        "Delivered"
                    ) === 0;


                /* -----------------------------------------
                   STATUS CLASS
                ----------------------------------------- */

                $statusClass = match (
                    $currentStatus
                ) {

                    "Pending" =>
                        "status-pending",

                    "Processing" =>
                        "status-processing",

                    "Shipped" =>
                        "status-shipped",

                    "Delivered" =>
                        "status-delivered",

                    "Cancelled" =>
                        "status-cancelled",

                    default =>
                        "status-pending"
                };


                /* -----------------------------------------
                   PAYMENT METHOD
                ----------------------------------------- */

                $paymentMethod =
                    getPaymentMethod($order);


                $isGCash =
                    $paymentMethod === "gcash";


                $isCOD =
                    $paymentMethod === "cod";

            ?>


            <!-- =================================================
                 ORDER CARD
            ================================================== -->

            <div class="order-card">


                <!-- =================================================
                     ORDER HEADER
                ================================================== -->

                <div class="order-top">

                    <div>

                        <p class="order-number">

                            Order #<?= (int)
                                $order["order_id"]
                            ?>

                        </p>


                        <p class="order-date">

                            <i class="bi bi-calendar3"></i>

                            <?= date(
                                "F j, Y g:i A",
                                strtotime(
                                    $order["created_at"]
                                )
                            ) ?>

                        </p>


                        <span
                            class="
                                status-badge
                                <?= $statusClass ?>
                            "
                        >

                            <?= htmlspecialchars(
                                $currentStatus
                            ) ?>

                        </span>


                        <?php if ($isDelivered): ?>

                            <span class="completed-badge">

                                <i class="bi bi-check-circle-fill"></i>

                                Completed

                            </span>

                        <?php endif; ?>

                    </div>


                    <!-- ORDER TOTAL -->

                    <div class="order-total-container">

                        <div class="order-total-label">

                            Order Total

                        </div>


                        <div class="order-total">

                            ₱<?= number_format(
                                $order["total_amount"],
                                2
                            ) ?>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     ORDER ITEMS
                ================================================== -->

                <div class="items-section">

                    <div class="items-title">

                        <i class="bi bi-box-seam"></i>

                        Products

                    </div>


                    <?php if (
                        !empty(
                            $orderItems[
                                $order["order_id"]
                            ]
                        )
                    ): ?>


                        <?php foreach (
                            $orderItems[
                                $order["order_id"]
                            ]
                            as $item
                        ): ?>


                            <div class="order-item">

                                <div class="order-item-left">


                                    <!-- PRODUCT IMAGE -->

                                    <div
                                        class="admin-product-image"
                                    >

                                        <?php if (
                                            !empty(
                                                $item["image"]
                                            )
                                        ): ?>

                                            <img
                                                src="../<?= htmlspecialchars(
                                                    $item["image"]
                                                ) ?>"
                                                alt="<?= htmlspecialchars(
                                                    $item["product_name"]
                                                ) ?>"
                                            >

                                        <?php else: ?>

                                            <div
                                                class="admin-product-placeholder"
                                            >

                                                <i class="bi bi-image"></i>

                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <!-- PRODUCT INFORMATION -->

                                    <div>

                                        <div class="item-name">

                                            <?= htmlspecialchars(
                                                $item["product_name"]
                                            ) ?>

                                        </div>


                                        <div class="item-quantity">

                                            ₱<?= number_format(
                                                $item["price"],
                                                2
                                            ) ?>

                                            ×

                                            <?= (int)
                                                $item["quantity"]
                                            ?>

                                        </div>

                                    </div>

                                </div>


                                <!-- SUBTOTAL -->

                                <div class="item-price">

                                    ₱<?= number_format(
                                        $item["subtotal"],
                                        2
                                    ) ?>

                                </div>

                            </div>


                        <?php endforeach; ?>


                    <?php else: ?>

                        <div class="text-muted small">

                            No product information available.

                        </div>

                    <?php endif; ?>

                </div>


                <!-- =================================================
                     PAYMENT INFORMATION
                ================================================== -->

                <div class="payment-information">

                    <div class="payment-header">

                        <div class="payment-header-icon">

                            <?php if ($isGCash): ?>

                                <i class="bi bi-phone-fill"></i>

                            <?php else: ?>

                                <i class="bi bi-cash-stack"></i>

                            <?php endif; ?>

                        </div>


                        <div>

                            <div class="payment-title">

                                Payment Information

                            </div>


                            <div class="payment-subtitle">

                                Customer's selected payment method

                            </div>

                        </div>

                    </div>


                    <!-- PAYMENT METHOD -->

                    <div class="payment-method-row">

                        <div class="payment-detail-icon">

                            <?php if ($isGCash): ?>

                                <i class="bi bi-phone"></i>

                            <?php else: ?>

                                <i class="bi bi-wallet2"></i>

                            <?php endif; ?>

                        </div>


                        <div class="payment-detail-content">

                            <span>
                                Payment Method
                            </span>


                            <?php if ($isGCash): ?>

                                <strong class="gcash-method">

                                    GCash

                                </strong>

                            <?php else: ?>

                                <strong class="cod-method">

                                    Cash on Delivery

                                </strong>

                            <?php endif; ?>

                        </div>

                    </div>


                    <?php if ($isGCash): ?>


                        <!-- GCASH NUMBER -->

                        <?php if (
                            !empty(
                                $order["gcash_number"]
                            )
                        ): ?>

                            <div class="payment-detail-row">

                                <div class="payment-detail-icon">

                                    <i class="bi bi-phone"></i>

                                </div>


                                <div class="payment-detail-content">

                                    <span>
                                        GCash Number
                                    </span>


                                    <strong>

                                        <?= htmlspecialchars(
                                            $order["gcash_number"]
                                        ) ?>

                                    </strong>

                                </div>

                            </div>

                        <?php endif; ?>


                        <!-- GCASH REFERENCE -->

                        <?php

                            $referenceNumber =
                                !empty(
                                    $order[
                                        "gcash_reference"
                                    ]
                                )
                                ? $order[
                                    "gcash_reference"
                                ]
                                : $order[
                                    "transaction_reference"
                                ];

                        ?>


                        <?php if (
                            !empty(
                                $referenceNumber
                            )
                        ): ?>

                            <div class="payment-detail-row">

                                <div
                                    class="
                                        payment-detail-icon
                                        reference-icon
                                    "
                                >

                                    <i class="bi bi-receipt"></i>

                                </div>


                                <div class="payment-detail-content">

                                    <span>
                                        GCash Reference Number
                                    </span>


                                    <strong>

                                        <?= htmlspecialchars(
                                            $referenceNumber
                                        ) ?>

                                    </strong>

                                </div>

                            </div>

                        <?php endif; ?>


                        <!-- =================================================
     PAYMENT STATUS
================================================= -->

<div class="payment-detail-row">

    <div class="payment-detail-icon">
        <i class="bi bi-check-circle"></i>
    </div>

    <div class="payment-detail-content">

        <span>
            Payment Status
        </span>

        <?php
        $currentPaymentStatus =
            $order["payment_status"] ?? "Pending";
        ?>

        <strong
            class="
                payment-status
                <?= strtolower($currentPaymentStatus) === "paid"
                    ? "payment-paid"
                    : "payment-pending"
                ?>
            "
        >
            <?= htmlspecialchars($currentPaymentStatus) ?>
        </strong>

    </div>

</div>


<!-- =================================================
     UPDATE PAYMENT STATUS
================================================= -->

<div class="payment-status-control">

    <form
        method="POST"
        action="update-payment-status.php"
        class="payment-status-form"
    >

        <input
            type="hidden"
            name="order_id"
            value="<?= (int)$order["order_id"] ?>"
        >

        <input
            type="hidden"
            name="customer_id"
            value="<?= (int)$customer["user_id"] ?>"
        >

        <select
            name="payment_status"
            required
        >

            <option
                value="Pending"
                <?= $currentPaymentStatus === "Pending"
                    ? "selected"
                    : ""
                ?>
            >
                Pending
            </option>

            <option
                value="Paid"
                <?= $currentPaymentStatus === "Paid"
                    ? "selected"
                    : ""
                ?>
            >
                Paid
            </option>

            <option
                value="Failed"
                <?= $currentPaymentStatus === "Failed"
                    ? "selected"
                    : ""
                ?>
            >
                Failed
            </option>

            <option
                value="Cancelled"
                <?= $currentPaymentStatus === "Cancelled"
                    ? "selected"
                    : ""
                ?>
            >
                Cancelled
            </option>

        </select>

        <button
            type="submit"
            class="payment-update-btn"
        >
            <i class="bi bi-credit-card"></i>
            Update Payment
        </button>

    </form>

</div>


                    <?php else: ?>


                        <!-- COD INFORMATION -->

                        <div class="cod-information">

                            <i class="bi bi-info-circle-fill"></i>


                            <div>

                                <strong>
                                    Cash on Delivery
                                </strong>


                                <span>
                                    Payment will be collected
                                    when the order is delivered.
                                </span>

                            </div>

                        </div>


                    <?php endif; ?>

                </div>


                <!-- =================================================
                     ORDER CONTROLS
                ================================================== -->

                <div class="order-controls">


                    <?php if ($isDelivered): ?>


                        <!-- =================================================
                             COMPLETED ORDER
                        ================================================== -->

                        <div class="completed-order-message">

                            <i class="bi bi-check-circle-fill"></i>

                            <div>

                                <strong>
                                    Order Completed
                                </strong>

                                <span>
                                    This order has already been delivered.
                                    Its status can no longer be changed.
                                </span>

                            </div>

                        </div>


                    <?php else: ?>


                        <!-- =================================================
                             ACTIVE ORDER STATUS FORM
                        ================================================== -->

                        <form
                            method="POST"
                            action="update-order-status.php"
                            class="status-form"
                        >


                            <input
                                type="hidden"
                                name="order_id"
                                value="<?= (int)
                                    $order["order_id"]
                                ?>"
                            >


                            <input
                                type="hidden"
                                name="customer_id"
                                value="<?= (int)
                                    $customer["user_id"]
                                ?>"
                            >


                            <select
                                name="status"
                                required
                            >

                                <option
                                    value="Pending"
                                    <?= $currentStatus === "Pending"
                                        ? "selected"
                                        : ""
                                    ?>
                                >
                                    Pending
                                </option>


                                <option
                                    value="Processing"
                                    <?= $currentStatus === "Processing"
                                        ? "selected"
                                        : ""
                                    ?>
                                >
                                    Processing
                                </option>


                                <option
                                    value="Shipped"
                                    <?= $currentStatus === "Shipped"
                                        ? "selected"
                                        : ""
                                    ?>
                                >
                                    Shipped
                                </option>


                                <option
                                    value="Delivered"
                                    <?= $currentStatus === "Delivered"
                                        ? "selected"
                                        : ""
                                    ?>
                                >
                                    Delivered
                                </option>


                                <option
                                    value="Cancelled"
                                    <?= $currentStatus === "Cancelled"
                                        ? "selected"
                                        : ""
                                    ?>
                                >
                                    Cancelled
                                </option>

                            </select>


                            <button
                                type="submit"
                                class="update-btn"
                            >

                                <i class="bi bi-check-lg"></i>

                                Update Status

                            </button>

                        </form>


                    <?php endif; ?>


                    <!-- VIEW TRACKING -->

                    <a
                        href="admin-track-order.php?order_id=<?= (int)
                            $order["order_id"]
                        ?>"
                        class="track-btn"
                        target="_blank"
                    >

                        <i class="bi bi-eye"></i>

                        View Tracking

                    </a>


                </div>


            </div>


        <?php endforeach; ?>


    <?php endif; ?>


</main>


<!-- =========================================================
     EXTRA STYLE FOR COMPLETED ORDERS
========================================================= -->

<style>

.completed-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-left: 7px;
    padding: 6px 10px;
    border-radius: 20px;
    background: #dcfce7;
    color: #15803d;
    font-size: 12px;
    font-weight: 700;
}

.completed-order-message {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    padding: 12px 15px;
    border-radius: 10px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
}

.completed-order-message > i {
    font-size: 22px;
}

.completed-order-message div {
    display: flex;
    flex-direction: column;
}

.completed-order-message strong {
    font-size: 14px;
    font-weight: 700;
}

.completed-order-message span {
    font-size: 12px;
    color: #4b7a5a;
}

@media (max-width: 768px) {

    .completed-order-message {
        width: 100%;
    }

    .completed-badge {
        margin-top: 6px;
        margin-left: 0;
    }

}

</style>


</body>

</html>