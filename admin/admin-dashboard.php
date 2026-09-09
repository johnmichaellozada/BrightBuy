<?php

session_start();

require_once "../db.php";
require_once "../includes/auth.php";


/* =====================================================
   REQUIRE ADMIN LOGIN
===================================================== */

requireAdmin($pdo);


/* =====================================================
   GET CUSTOMERS
===================================================== */

$customerStmt = $pdo->query("
    SELECT
        u.user_id,
        u.first_name,
        u.last_name,
        u.email,
        u.phone,
        u.created_at,
        COUNT(o.order_id) AS total_orders
    FROM users u
    LEFT JOIN orders o
        ON u.user_id = o.user_id
    WHERE u.role = 'customer'
    GROUP BY
        u.user_id,
        u.first_name,
        u.last_name,
        u.email,
        u.phone,
        u.created_at
    ORDER BY u.created_at DESC
");

$customers = $customerStmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   DASHBOARD STATISTICS
===================================================== */

$totalCustomers = count($customers);


/* =====================================================
   ORDER STATISTICS
===================================================== */

$orderStmt = $pdo->query("
    SELECT
        COUNT(*) AS total_orders,

        SUM(
            CASE
                WHEN LOWER(TRIM(status)) = 'pending'
                THEN 1
                ELSE 0
            END
        ) AS pending_orders,

        SUM(
            CASE
                WHEN LOWER(TRIM(status)) = 'processing'
                THEN 1
                ELSE 0
            END
        ) AS processing_orders,

        SUM(
            CASE
                WHEN LOWER(TRIM(status)) = 'shipped'
                THEN 1
                ELSE 0
            END
        ) AS shipped_orders,

        SUM(
            CASE
                WHEN LOWER(TRIM(status)) = 'delivered'
                THEN 1
                ELSE 0
            END
        ) AS delivered_orders,

        SUM(
            CASE
                WHEN LOWER(TRIM(status)) IN ('cancelled', 'canceled')
                THEN 1
                ELSE 0
            END
        ) AS cancelled_orders

    FROM orders
");

$orderStats = $orderStmt->fetch(PDO::FETCH_ASSOC);


$totalOrders = (int)($orderStats["total_orders"] ?? 0);
$pendingOrders = (int)($orderStats["pending_orders"] ?? 0);
$processingOrders = (int)($orderStats["processing_orders"] ?? 0);
$shippedOrders = (int)($orderStats["shipped_orders"] ?? 0);
$deliveredOrders = (int)($orderStats["delivered_orders"] ?? 0);
$cancelledOrders = (int)($orderStats["cancelled_orders"] ?? 0);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard | BrightBuy</title>


    <!-- =========================
         BOOTSTRAP
    ========================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =========================
         BOOTSTRAP ICONS
    ========================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- =========================
         GOOGLE FONT
    ========================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- =========================
         MAIN STYLES
    ========================== -->

    <link
        rel="stylesheet"
        href="../css/styles.css"
    >


    <style>

        /* =========================
           GLOBAL
        ========================== */

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            font-family: "Poppins", sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }


        /* =========================
           ADMIN HEADER
        ========================== */

        .admin-header {

            background: #0d47a1;

            color: white;

            padding: 18px 35px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            box-shadow:
                0 3px 12px rgba(0,0,0,0.10);

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
           DASHBOARD CONTAINER
        ========================== */

        .dashboard-container {

            max-width: 1250px;

            margin: 35px auto;

            padding: 0 20px;

        }


        /* =========================
           WELCOME SECTION
        ========================== */

        .welcome-section {

            margin-bottom: 25px;

        }


        .welcome-section h2 {

            font-weight: 700;

            margin-bottom: 5px;

            color: #0d47a1;

        }


        .welcome-section p {

            color: #6b7280;

            margin: 0;

        }


        /* =========================
           STATISTICS
        ========================== */

        .stat-card {

            background: white;

            border-radius: 14px;

            padding: 22px;

            height: 100%;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.06);

            position: relative;

            overflow: hidden;

        }


        .stat-card::after {

            content: "";

            position: absolute;

            width: 100px;

            height: 100px;

            right: -30px;

            bottom: -35px;

            background: #f4f6fa;

            border-radius: 50%;

        }


        .stat-icon {

            width: 48px;

            height: 48px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;

            background: #e8f0fe;

            color: #0d47a1;

            margin-bottom: 12px;

            position: relative;

            z-index: 2;

        }


        .stat-number {

            font-size: 27px;

            font-weight: 700;

            margin: 0;

            color: #082b73;

            position: relative;

            z-index: 2;

        }


        .stat-label {

            color: #6b7280;

            font-size: 14px;

            position: relative;

            z-index: 2;

        }


        /* =========================
           ORDER STATUS BREAKDOWN
        ========================== */

        .status-breakdown {
            position: relative;
            z-index: 3;
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 0;
        }

        .status-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 600;
        }

        .status-name {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #6b7280;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            min-width: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-dot.pending {
            background: #f4b400;
        }

        .status-dot.processing {
            background: #1264e8;
        }

        .status-dot.shipped {
            background: #7c3aed;
        }

        .status-dot.delivered {
            background: #16a34a;
        }

        .status-dot.cancelled {
            background: #ef4444;
        }

        .status-count {
            color: #082b73;
            font-weight: 700;
        }


        /* =========================
           CUSTOMER SECTION
        ========================== */

        .customers-card {

            background: white;

            border-radius: 16px;

            margin-top: 30px;

            box-shadow:
                0 3px 15px rgba(0,0,0,0.06);

            overflow: hidden;

        }


        .customers-header {

            padding: 22px 25px;

            border-bottom: 1px solid #eee;

            display: flex;

            justify-content: space-between;

            align-items: center;

        }


        .customers-header h4 {

            margin: 0;

            font-weight: 700;

            color: #082b73;

        }


        .customers-header h4 i {

            color: #1264e8;

        }


        .customer-row {

            padding: 18px 25px;

            border-bottom: 1px solid #eee;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

        }


        .customer-row:last-child {

            border-bottom: none;

        }


        .customer-info {

            display: flex;

            align-items: center;

            gap: 15px;

            min-width: 0;

        }


        /* =========================
           CUSTOMER AVATAR
        ========================== */

        .customer-avatar {

            width: 50px;

            height: 50px;

            min-width: 50px;

            border-radius: 50%;

            background: #e8f0fe;

            color: #0d47a1;

            border: 2px solid #ffbf00;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 21px;

            font-weight: 700;

        }


        /* =========================
           CUSTOMER INFORMATION
        ========================== */

        .customer-name {

            font-weight: 600;

            margin: 0;

            color: #082b73;

        }


        .customer-email {

            font-size: 13px;

            color: #6b7280;

            margin: 2px 0 0;

            word-break: break-word;

        }


        .customer-phone {

            font-size: 12px;

            color: #888;

            margin: 2px 0 0;

        }


        /* =========================
           ORDER BADGE
        ========================== */

        .order-badge {

            display: inline-block;

            background: #eef4ff;

            color: #0d47a1;

            padding: 7px 12px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: 600;

            white-space: nowrap;

        }


        .order-badge i {

            margin-right: 3px;

        }


        /* =========================
           VIEW ORDERS BUTTON
        ========================== */

        .view-customer-btn {

            background: #0d47a1;

            color: white;

            border: none;

            border-radius: 8px;

            padding: 9px 15px;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

            white-space: nowrap;

            display: inline-block;

        }


        .view-customer-btn:hover {

            background: #083579;

            color: white;

        }


        /* =========================
           EMPTY CUSTOMER
        ========================== */

        .empty-customers {

            padding: 50px 20px;

            text-align: center;

            color: #777;

        }


        .empty-customers i {

            font-size: 45px;

            display: block;

            margin-bottom: 12px;

        }


        .empty-customers h5 {

            font-weight: 600;

            color: #082b73;

        }


        /* =========================
           MOBILE
        ========================== */

        @media (max-width: 768px) {


            .admin-header {

                padding: 15px 18px;

            }


            .admin-brand h4 {

                font-size: 17px;

            }


            .admin-brand small {

                font-size: 11px;

            }


            .admin-actions .btn {

                font-size: 12px;

                padding: 7px 10px;

            }


            .dashboard-container {

                margin: 25px auto;

                padding: 0 15px;

            }


            .welcome-section h2 {

                font-size: 25px;

            }


            .customer-row {

                align-items: flex-start;

                flex-direction: column;

            }


            .customer-actions {

                width: 100%;

                display: flex;

                flex-wrap: wrap;

            }


            .view-customer-btn {

                display: block;

                text-align: center;

            }

        }


        /* =========================
           SMALL MOBILE
        ========================== */

        @media (max-width: 480px) {


            .admin-header {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;

            }


            .admin-actions {

                width: 100%;

            }


            .admin-actions a {

                flex: 1;

                text-align: center;

            }


            .customers-header {

                padding: 18px;

            }


            .customer-row {

                padding: 18px;

            }


            .customer-info {

                width: 100%;

            }


            .customer-actions {

                width: 100%;

                flex-direction: column;

                align-items: stretch !important;

            }


            .order-badge {

                text-align: center;

            }


            .view-customer-btn {

                width: 100%;

            }

        }

    </style>

</head>


<body class="admin-dashboard-page">


<!-- =================================================
     ADMIN HEADER
================================================= -->

<header class="admin-header">


    <div class="admin-brand">

        <i class="bi bi-shield-lock-fill"></i>

        <div>

            <h4>
                BrightBuy Admin
            </h4>

            <small>
                Administrator Dashboard
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



<!-- =================================================
     DASHBOARD
================================================= -->

<main class="dashboard-container">


    <!-- =========================
         WELCOME
    ========================== -->

    <div class="welcome-section">

        <h2>

            Welcome,
            <?= htmlspecialchars($_SESSION["first_name"] ?? "Admin") ?>!

        </h2>


        <p>
            Manage your BrightBuy customers and their orders.
        </p>

    </div>



    <!-- =================================================
         STATISTICS
    ================================================= -->

    <div class="row g-3">


        <!-- =========================
             TOTAL CUSTOMERS
        ========================== -->

        <div class="col-md-4">

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="bi bi-people-fill"></i>

                </div>


                <p class="stat-number">

                    <?= $totalCustomers ?>

                </p>


                <div class="stat-label">

                    Total Customers

                </div>

            </div>

        </div>



        <!-- =========================
             TOTAL ORDERS
        ========================== -->

        <div class="col-md-4">

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="bi bi-bag-check-fill"></i>

                </div>


                <p class="stat-number">

                    <?= $totalOrders ?>

                </p>


                <div class="stat-label">

                    Total Orders

                </div>

            </div>

        </div>



                <!-- =================================================
             ORDER STATUS
        ================================================== -->

        <div class="col-md-4">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>

                <div class="status-breakdown">

                    <div class="status-item">
                        <span class="status-name">
                            <span class="status-dot pending"></span>
                            Pending
                        </span>
                        <span class="status-count"><?= $pendingOrders ?></span>
                    </div>

                    <div class="status-item">
                        <span class="status-name">
                            <span class="status-dot processing"></span>
                            Processing
                        </span>
                        <span class="status-count"><?= $processingOrders ?></span>
                    </div>

                    <div class="status-item">
                        <span class="status-name">
                            <span class="status-dot shipped"></span>
                            Shipped
                        </span>
                        <span class="status-count"><?= $shippedOrders ?></span>
                    </div>

                    <div class="status-item">
                        <span class="status-name">
                            <span class="status-dot delivered"></span>
                            Delivered
                        </span>
                        <span class="status-count"><?= $deliveredOrders ?></span>
                    </div>

                    <div class="status-item">
                        <span class="status-name">
                            <span class="status-dot cancelled"></span>
                            Cancelled
                        </span>
                        <span class="status-count"><?= $cancelledOrders ?></span>
                    </div>

                </div>

                <div class="stat-label">
                    Order Status
                </div>

            </div>

        </div>


    <!-- =================================================
         CUSTOMERS
    ================================================== -->

    <div class="customers-card">


        <!-- =========================
             CUSTOMERS HEADER
        ========================== -->

        <div class="customers-header">


            <h4>

                <i class="bi bi-people-fill"></i>

                Customers

            </h4>


            <span class="text-muted">

                <?= $totalCustomers ?>

                <?= $totalCustomers === 1
                    ? "customer"
                    : "customers"
                ?>

            </span>


        </div>



        <!-- =========================
             NO CUSTOMERS
        ========================== -->

        <?php if (empty($customers)): ?>


            <div class="empty-customers">


                <i class="bi bi-people"></i>


                <h5>

                    No customers found

                </h5>


                <p>

                    Registered customers will appear here.

                </p>


            </div>


        <?php else: ?>


            <!-- =================================================
                 CUSTOMER LOOP
            ================================================== -->

            <?php foreach ($customers as $customer): ?>


                <div class="customer-row">


                    <!-- =========================
                         CUSTOMER INFORMATION
                    ========================== -->

                    <div class="customer-info">


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


                            <p class="customer-name">

                                <?= htmlspecialchars(
                                    $customer["first_name"]
                                    . " "
                                    . $customer["last_name"]
                                ) ?>

                            </p>


                            <p class="customer-email">

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



                    <!-- =========================
                         CUSTOMER ACTIONS
                    ========================== -->

                    <div
                        class="d-flex align-items-center gap-3 customer-actions"
                    >


                        <!-- =========================
                             REAL ORDER COUNT
                        ========================== -->

                        <span class="order-badge">


                            <i class="bi bi-bag"></i>


                            <?= (int)$customer["total_orders"] ?>


                            <?= (int)$customer["total_orders"] === 1
                                ? "Order"
                                : "Orders"
                            ?>


                        </span>



                        <!-- =========================
                             VIEW ORDERS
                        ========================== -->

                        <a
                            href="customer-orders.php?user_id=<?= (int)$customer["user_id"] ?>"
                            class="view-customer-btn"
                        >

                            <i class="bi bi-eye"></i>

                            View Orders

                        </a>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php endif; ?>


    </div>


</main>



<!-- =================================================
     BOOTSTRAP JS
================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>