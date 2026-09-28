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
   TOTAL CUSTOMERS
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


/* =====================================================
   ORDER COUNTS
===================================================== */

$totalOrders =
    (int)($orderStats["total_orders"] ?? 0);

$pendingOrders =
    (int)($orderStats["pending_orders"] ?? 0);

$processingOrders =
    (int)($orderStats["processing_orders"] ?? 0);

$shippedOrders =
    (int)($orderStats["shipped_orders"] ?? 0);

$deliveredOrders =
    (int)($orderStats["delivered_orders"] ?? 0);

$cancelledOrders =
    (int)($orderStats["cancelled_orders"] ?? 0);


/* =====================================================
   TOTAL INCOME TODAY
   ONLY DELIVERED ORDERS
===================================================== */

$todayIncomeStmt = $pdo->query("
    SELECT
        COALESCE(SUM(total_amount), 0) AS total_income
    FROM orders
    WHERE LOWER(TRIM(status)) = 'delivered'
      AND DATE(created_at) = CURDATE()
");

$todayIncomeData =
    $todayIncomeStmt->fetch(PDO::FETCH_ASSOC);

$totalIncomeToday =
    (float)($todayIncomeData["total_income"] ?? 0);


/* =====================================================
   TOTAL INCOME THIS MONTH
   ONLY DELIVERED ORDERS
===================================================== */

$monthIncomeStmt = $pdo->query("
    SELECT
        COALESCE(SUM(total_amount), 0) AS total_income
    FROM orders
    WHERE LOWER(TRIM(status)) = 'delivered'
      AND YEAR(created_at) = YEAR(CURDATE())
      AND MONTH(created_at) = MONTH(CURDATE())
");

$monthIncomeData =
    $monthIncomeStmt->fetch(PDO::FETCH_ASSOC);

$totalIncomeMonth =
    (float)($monthIncomeData["total_income"] ?? 0);


/* =====================================================
   CURRENT MONTH NAME
===================================================== */

$currentMonth =
    date("F Y");

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
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- =================================================
         GOOGLE FONT
    ================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- =================================================
         MAIN STYLES
    ================================================== -->

    <link
        rel="stylesheet"
        href="../css/styles.css"
    >

</head>


<body class="admin-dashboard-page">


<!-- =====================================================
     ADMIN HEADER
====================================================== -->

<header class="admin-header">

    <div class="admin-brand">

        <div class="admin-brand-icon">

            <i class="bi bi-shield-lock-fill"></i>

        </div>

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
            class="admin-inventory-btn"
        >

            <i class="bi bi-box-seam"></i>

            Inventory

        </a>


        <a
            href="logout.php"
            class="admin-logout-btn"
        >

            <i class="bi bi-box-arrow-right"></i>

            Logout

        </a>

    </div>

</header>


<!-- =====================================================
     MAIN DASHBOARD
====================================================== -->

<main class="dashboard-container">


    <!-- =================================================
         WELCOME
    ================================================== -->

    <section class="welcome-section">

        <span class="dashboard-eyebrow">
            ADMINISTRATION
        </span>

        <h2>

            Welcome,
            <?= htmlspecialchars(
                $_SESSION["first_name"] ?? "Admin"
            ) ?>!

        </h2>

        <p>
            Manage your BrightBuy customers, orders, sales, and inventory.
        </p>

    </section>


    <!-- =================================================
         INCOME SECTION
    ================================================== -->

    <section class="income-section">

        <div class="section-heading">

            <div>

                <span class="section-eyebrow">
                    SALES OVERVIEW
                </span>

                <h3>
                    Income
                </h3>

            </div>

            <div class="income-date">

                <i class="bi bi-calendar3"></i>

                <?= date("F d, Y") ?>

            </div>

        </div>


        <div class="row g-3">


            <!-- =========================================
                 TODAY INCOME
            ========================================== -->

            <div class="col-md-6">

                <div class="income-card today-income-card">

                    <div class="income-card-content">

                        <div class="income-icon">

                            <i class="bi bi-cash-stack"></i>

                        </div>

                        <div>

                            <span class="income-label">
                                TOTAL INCOME TODAY
                            </span>

                            <h2>
                                ₱<?= number_format(
                                    $totalIncomeToday,
                                    2
                                ) ?>
                            </h2>

                            <p>
                                Completed orders for today
                            </p>

                        </div>

                    </div>


                    <div class="income-decoration">

                        <i class="bi bi-graph-up-arrow"></i>

                    </div>

                </div>

            </div>


            <!-- =========================================
                 MONTH INCOME
            ========================================== -->

            <div class="col-md-6">

                <div class="income-card month-income-card">

                    <div class="income-card-content">

                        <div class="income-icon">

                            <i class="bi bi-bar-chart-fill"></i>

                        </div>

                        <div>

                            <span class="income-label">
                                TOTAL INCOME THIS MONTH
                            </span>

                            <h2>
                                ₱<?= number_format(
                                    $totalIncomeMonth,
                                    2
                                ) ?>
                            </h2>

                            <p>
                                Completed orders for <?= htmlspecialchars($currentMonth) ?>
                            </p>

                        </div>

                    </div>


                    <div class="income-decoration">

                        <i class="bi bi-calendar-check"></i>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- =================================================
         ORDER STATISTICS
    ================================================== -->

    <section class="statistics-section">

        <div class="section-heading">

            <div>

                <span class="section-eyebrow">
                    STORE OVERVIEW
                </span>

                <h3>
                    Dashboard Statistics
                </h3>

            </div>

        </div>


        <div class="row g-3">


            <!-- =========================================
                 TOTAL CUSTOMERS
            ========================================== -->

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


            <!-- =========================================
                 TOTAL ORDERS
            ========================================== -->

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


            <!-- =========================================
                 ORDER STATUS
            ========================================== -->

            <div class="col-md-4">

                <div class="stat-card order-status-card">

                    <div class="stat-icon">

                        <i class="bi bi-hourglass-split"></i>

                    </div>


                    <div class="status-breakdown">


                        <div class="status-item">

                            <span class="status-name">

                                <span class="status-dot pending"></span>

                                Pending

                            </span>

                            <span class="status-count">

                                <?= $pendingOrders ?>

                            </span>

                        </div>


                        <div class="status-item">

                            <span class="status-name">

                                <span class="status-dot processing"></span>

                                Processing

                            </span>

                            <span class="status-count">

                                <?= $processingOrders ?>

                            </span>

                        </div>


                        <div class="status-item">

                            <span class="status-name">

                                <span class="status-dot shipped"></span>

                                Shipped

                            </span>

                            <span class="status-count">

                                <?= $shippedOrders ?>

                            </span>

                        </div>


                        <div class="status-item">

                            <span class="status-name">

                                <span class="status-dot delivered"></span>

                                Delivered

                            </span>

                            <span class="status-count">

                                <?= $deliveredOrders ?>

                            </span>

                        </div>


                        <div class="status-item">

                            <span class="status-name">

                                <span class="status-dot cancelled"></span>

                                Cancelled

                            </span>

                            <span class="status-count">

                                <?= $cancelledOrders ?>

                            </span>

                        </div>


                    </div>


                    <div class="stat-label">

                        Order Status

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- =================================================
         CUSTOMERS
    ================================================== -->

    <section class="customers-card">


        <!-- =============================================
             CUSTOMERS HEADER
        ============================================== -->

        <div class="customers-header">

            <div>

                <span class="section-eyebrow">
                    CUSTOMER MANAGEMENT
                </span>

                <h4>

                    <i class="bi bi-people-fill"></i>

                    Customers

                </h4>

            </div>


            <span class="customers-count">

                <?= $totalCustomers ?>

                <?= $totalCustomers === 1
                    ? "customer"
                    : "customers"
                ?>

            </span>

        </div>


        <!-- =============================================
             NO CUSTOMERS
        ============================================== -->

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


            <!-- =========================================
                 CUSTOMER LOOP
            ========================================== -->

            <?php foreach ($customers as $customer): ?>

                <div class="customer-row">


                    <!-- =================================
                         CUSTOMER INFORMATION
                    ================================== -->

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


                    <!-- =================================
                         CUSTOMER ACTIONS
                    ================================== -->

                    <div
                        class="customer-actions"
                    >


                        <!-- ORDER COUNT -->

                        <span class="order-badge">

                            <i class="bi bi-bag"></i>

                            <?= (int)$customer["total_orders"] ?>

                            <?= (int)$customer["total_orders"] === 1
                                ? "Order"
                                : "Orders"
                            ?>

                        </span>


                        <!-- VIEW ORDERS -->

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


    </section>


</main>


<!-- =====================================================
     BOOTSTRAP JS
====================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>
</html>