<?php

session_start();
require_once "../db.php";


/* =========================
   ADMIN ACCESS CHECK
========================= */

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: admin-login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$userStmt = $pdo->prepare("
    SELECT role
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$userStmt->execute([$user_id]);
$currentUser = $userStmt->fetch();

if (!$currentUser || $currentUser["role"] !== "admin") {
    die("Access denied. Administrator privileges required.");
}


/* =========================
   GET CUSTOMER ID
========================= */

$customer_id = filter_input(
    INPUT_GET,
    "user_id",
    FILTER_VALIDATE_INT
);

if (!$customer_id) {
    header("Location: admin-dashboard.php");
    exit;
}


/* =========================
   GET CUSTOMER INFORMATION
========================= */

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

$customerStmt->execute([$customer_id]);
$customer = $customerStmt->fetch();


if (!$customer) {
    die("Customer not found.");
}


/* =========================
   GET CUSTOMER ORDERS
========================= */

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

$orderStmt->execute([$customer_id]);
$orders = $orderStmt->fetchAll();


/* =========================
   GET ORDER ITEMS
========================= */

$orderItems = [];

if (!empty($orders)) {

    $orderIds = array_column($orders, "order_id");

    $placeholders = implode(
        ",",
        array_fill(0, count($orderIds), "?")
    );

    $itemsStmt = $pdo->prepare("
        SELECT
            oi.order_id,
            oi.product_id,
            oi.quantity,
            oi.price,
            oi.subtotal,
            p.product_name
        FROM order_items oi
        INNER JOIN products p
            ON oi.product_id = p.product_id
        WHERE oi.order_id IN ($placeholders)
        ORDER BY oi.order_item_id ASC
    ");

    $itemsStmt->execute($orderIds);

    while ($item = $itemsStmt->fetch()) {

        $orderItems[$item["order_id"]][] = $item;

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
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            background: #f5f7fb;
            font-family: "Poppins", sans-serif;
            color: #1f2937;
        }


        /* =========================
           HEADER
        ========================= */

        .admin-header {

            background: #0d47a1;

            color: white;

            padding: 18px 35px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            box-shadow:
                0 3px 12px rgba(0, 0, 0, 0.10);

        }


        .admin-brand {

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .admin-brand i {

            font-size: 28px;

        }


        .admin-brand h4 {

            margin: 0;

            font-weight: 700;

        }


        .admin-brand small {

            opacity: 0.85;

        }


        .admin-actions {

            display: flex;

            gap: 10px;

        }


        .admin-actions a {

            text-decoration: none;

        }


        /* =========================
           MAIN
        ========================= */

        .orders-container {

            max-width: 1100px;

            margin: 35px auto;

            padding: 0 20px;

        }


        /* =========================
           BACK BUTTON
        ========================= */

        .back-link {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            color: #0d47a1;

            text-decoration: none;

            font-weight: 600;

            margin-bottom: 20px;

        }


        .back-link:hover {

            color: #083579;

        }


        /* =========================
           CUSTOMER CARD
        ========================= */

        .customer-card {

            background: white;

            border-radius: 16px;

            padding: 25px;

            box-shadow:
                0 3px 15px rgba(0, 0, 0, 0.06);

            margin-bottom: 25px;

        }


        .customer-profile {

            display: flex;

            align-items: center;

            gap: 18px;

        }


        .customer-avatar {

            width: 65px;

            height: 65px;

            min-width: 65px;

            border-radius: 50%;

            background: #e8f0fe;

            color: #0d47a1;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 27px;

            font-weight: 700;

        }


        .customer-name {

            margin: 0;

            font-size: 22px;

            font-weight: 700;

        }


        .customer-email {

            margin: 3px 0;

            color: #6b7280;

        }


        .customer-phone {

            margin: 0;

            font-size: 13px;

            color: #888;

        }


        /* =========================
           ORDERS HEADER
        ========================= */

        .orders-title {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 18px;

        }


        .orders-title h3 {

            margin: 0;

            font-weight: 700;

        }


        .orders-count {

            background: #e8f0fe;

            color: #0d47a1;

            padding: 7px 13px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: 600;

        }


        /* =========================
           ORDER CARD
        ========================= */

        .order-card {

            background: white;

            border-radius: 16px;

            padding: 23px;

            margin-bottom: 18px;

            box-shadow:
                0 3px 15px rgba(0, 0, 0, 0.06);

        }


        .order-top {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            padding-bottom: 17px;

            border-bottom: 1px solid #eee;

        }


        .order-number {

            margin: 0;

            font-size: 18px;

            font-weight: 700;

        }


        .order-date {

            margin: 4px 0 0;

            color: #777;

            font-size: 13px;

        }


        .order-total {

            font-size: 18px;

            font-weight: 700;

            color: #0d47a1;

        }


        /* =========================
           STATUS BADGES
        ========================= */

        .status-badge {

            display: inline-block;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

            margin-top: 8px;

        }


        .status-pending {

            background: #fff3cd;

            color: #856404;

        }


        .status-processing {

            background: #cfe2ff;

            color: #084298;

        }


        .status-shipped {

            background: #d1ecf1;

            color: #055160;

        }


        .status-delivered {

            background: #d1e7dd;

            color: #0f5132;

        }


        .status-cancelled {

            background: #f8d7da;

            color: #842029;

        }


        /* =========================
           ORDER ITEMS
        ========================= */

        .items-section {

            padding: 18px 0;

        }


        .items-title {

            font-weight: 600;

            margin-bottom: 10px;

        }


        .order-item {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 9px 0;

            border-bottom: 1px solid #f1f1f1;

        }


        .order-item:last-child {

            border-bottom: none;

        }


        .item-name {

            font-weight: 500;

        }


        .item-quantity {

            font-size: 13px;

            color: #777;

        }


        .item-price {

            font-weight: 600;

            white-space: nowrap;

        }


        /* =========================
           STATUS FORM
        ========================= */

        .order-controls {

            border-top: 1px solid #eee;

            padding-top: 18px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

        }


        .status-form {

            display: flex;

            align-items: center;

            gap: 8px;

        }


        .status-form select {

            min-width: 160px;

            border-radius: 8px;

            border: 1px solid #ced4da;

            padding: 9px 12px;

            font-family: "Poppins", sans-serif;

        }


        .update-btn {

            border: none;

            background: #0d47a1;

            color: white;

            border-radius: 8px;

            padding: 9px 15px;

            font-size: 13px;

            font-weight: 600;

        }


        .update-btn:hover {

            background: #083579;

        }


        .track-btn {

            text-decoration: none;

            border: 1px solid #0d47a1;

            color: #0d47a1;

            border-radius: 8px;

            padding: 8px 14px;

            font-size: 13px;

            font-weight: 600;

        }


        .track-btn:hover {

            background: #0d47a1;

            color: white;

        }


        /* =========================
           EMPTY ORDERS
        ========================= */

        .empty-orders {

            background: white;

            border-radius: 16px;

            padding: 60px 20px;

            text-align: center;

            box-shadow:
                0 3px 15px rgba(0, 0, 0, 0.06);

        }


        .empty-orders i {

            font-size: 50px;

            color: #aaa;

            display: block;

            margin-bottom: 15px;

        }


        .empty-orders h5 {

            font-weight: 600;

        }


        .empty-orders p {

            color: #777;

            margin: 0;

        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 768px) {

            .admin-header {

                padding: 15px 18px;

            }


            .admin-brand h4 {

                font-size: 17px;

            }


            .admin-actions .btn {

                font-size: 12px;

                padding: 7px 10px;

            }


            .customer-profile {

                align-items: flex-start;

            }


            .order-top {

                flex-direction: column;

            }


            .order-controls {

                flex-direction: column;

                align-items: stretch;

            }


            .status-form {

                flex-direction: column;

                align-items: stretch;

            }


            .status-form select {

                width: 100%;

            }


            .update-btn {

                width: 100%;

            }


            .track-btn {

                display: block;

                text-align: center;

            }


            .order-item {

                gap: 10px;

            }

        }

    </style>

</head>


<body>


<!-- =========================
     ADMIN HEADER
========================= -->

<header class="admin-header">

    <div class="admin-brand">

        <i class="bi bi-shield-lock-fill"></i>

        <div>

            <h4>BrightBuy Admin</h4>

            <small>
                Customer Orders
            </small>

        </div>

    </div>


    <div class="admin-actions">

        <a
            href="admin-products.php"
            class="btn btn-light btn-sm"
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


<!-- =========================
     MAIN CONTENT
========================= -->

<main class="orders-container">


    <a
        href="admin-dashboard.php"
        class="back-link"
    >
        <i class="bi bi-arrow-left"></i>
        Back to Customers
    </a>


    <!-- =========================
         CUSTOMER INFORMATION
    ========================= -->

    <div class="customer-card">

        <div class="customer-profile">


            <div class="customer-avatar">

                <?= strtoupper(
                    substr($customer["first_name"], 0, 1)
                ) ?>

            </div>


            <div>

                <h2 class="customer-name">

                    <?= htmlspecialchars(
                        $customer["first_name"] . " " . $customer["last_name"]
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


    <!-- =========================
         ORDERS TITLE
    ========================= -->

    <div class="orders-title">

        <h3>
            <i class="bi bi-bag-check-fill text-primary"></i>
            Orders
        </h3>


        <span class="orders-count">

            <?= count($orders) ?>

            <?= count($orders) === 1
                ? "Order"
                : "Orders" ?>

        </span>

    </div>


    <!-- =========================
         NO ORDERS
    ========================= -->

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


        <!-- =========================
             ORDER LIST
        ========================= -->

        <?php foreach ($orders as $order): ?>

            <?php

                $statusClass = match ($order["status"]) {

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

            ?>


            <div class="order-card">


                <!-- ORDER TOP -->

                <div class="order-top">


                    <div>

                        <p class="order-number">

                            Order #<?= (int)$order["order_id"] ?>

                        </p>


                        <p class="order-date">

                            <i class="bi bi-calendar3"></i>

                            <?= date(
                                "F j, Y g:i A",
                                strtotime($order["created_at"])
                            ) ?>

                        </p>


                        <span
                            class="status-badge <?= $statusClass ?>"
                        >

                            <?= htmlspecialchars(
                                $order["status"]
                            ) ?>

                        </span>

                    </div>


                    <div class="text-md-end">

                        <div class="text-muted small">
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


                <!-- =========================
                     ORDER ITEMS
                ========================= -->

                <div class="items-section">

                    <div class="items-title">

                        <i class="bi bi-box-seam"></i>
                        Products

                    </div>


                    <?php if (!empty($orderItems[$order["order_id"]])): ?>


                        <?php foreach (
                            $orderItems[$order["order_id"]]
                            as $item
                        ): ?>

                            <div class="order-item">


                                <div>

                                    <div class="item-name">

                                        <?= htmlspecialchars(
                                            $item["product_name"]
                                        ) ?>

                                    </div>


                                    <div class="item-quantity">

                                        Quantity:
                                        <?= (int)$item["quantity"] ?>

                                    </div>

                                </div>


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


                <!-- =========================
                     ORDER CONTROLS
                ========================= -->

                <div class="order-controls">


                    <form
                        method="POST"
                        action="update-order-status.php"
                        class="status-form"
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
                            name="status"
                            required
                        >

                            <option
                                value="Pending"
                                <?= $order["status"] === "Pending"
                                    ? "selected"
                                    : "" ?>
                            >
                                Pending
                            </option>

                            <option
                                value="Processing"
                                <?= $order["status"] === "Processing"
                                    ? "selected"
                                    : "" ?>
                            >
                                Processing
                            </option>

                            <option
                                value="Shipped"
                                <?= $order["status"] === "Shipped"
                                    ? "selected"
                                    : "" ?>
                            >
                                Shipped
                            </option>

                            <option
                                value="Delivered"
                                <?= $order["status"] === "Delivered"
                                    ? "selected"
                                    : "" ?>
                            >
                                Delivered
                            </option>

                            <option
                                value="Cancelled"
                                <?= $order["status"] === "Cancelled"
                                    ? "selected"
                                    : "" ?>
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


                    <a
                        href="track-order.php?order_id=<?= (int)$order["order_id"] ?>"
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


</body>

</html>