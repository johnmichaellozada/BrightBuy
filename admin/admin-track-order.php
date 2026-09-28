<?php

session_start();
require_once "../db.php";


/* =====================================================
   ADMIN ACCESS CHECK
===================================================== */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: admin-login.php");
    exit;
}

$user_id = $_SESSION["user_id"];


/* =====================================================
   VERIFY ADMIN ROLE FROM DATABASE
===================================================== */

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
            o.user_id,
            o.total_amount,
            o.status,
            o.created_at,

            u.first_name,
            u.last_name,
            u.email,

            a.recipient_name,
            a.phone,
            a.address_line,
            a.barangay,
            a.city,
            a.province,
            a.postal_code

        FROM orders o

        INNER JOIN users u
            ON o.user_id = u.user_id

        LEFT JOIN addresses a
            ON o.address_id = a.address_id

        WHERE o.order_id = ?

        LIMIT 1
    ");


    $orderStmt->execute([
        $order_id
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

            ORDER BY oi.order_item_id ASC
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

    <title>
        Order Tracking | BrightBuy Admin
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    >


    <!-- Poppins -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- BrightBuy CSS -->

    <link
        rel="stylesheet"
        href="../css/styles.css"
    >

</head>


<body class="track-order-page">


<div class="track-container">


    <!-- =================================================
         PAGE HEADER
    ================================================== -->

    <section class="track-header">

        <div class="track-header-content">

            <div class="track-header-icon">

                <i class="bi bi-shield-check"></i>

            </div>


            <h1>
                Order Tracking
            </h1>


            <p>
                Admin view of customer order tracking.
            </p>

        </div>

    </section>


    <?php if (!$order): ?>

        <!-- =================================================
             NO ORDER
        ================================================== -->

        <div class="card tracking-card">

            <div class="card-body text-center p-5">

                <i
                    class="bi bi-search"
                    style="font-size: 60px;"
                ></i>


                <h3 class="fw-bold mt-3">
                    Order Not Found
                </h3>


                <p class="text-muted">
                    The selected order could not be found.
                </p>


                <a
                    href="admin-dashboard.php"
                    class="btn btn-primary px-4"
                >

                    <i class="bi bi-arrow-left"></i>

                    Back to Customers

                </a>

            </div>

        </div>


    <?php else: ?>


        <!-- =================================================
             CUSTOMER INFORMATION
        ================================================== -->

        <div class="card tracking-card mb-4">

            <div class="card-body p-4">

                <div
                    class="d-flex justify-content-between
                    align-items-center flex-wrap"
                >

                    <div>

                        <small class="text-muted">
                            CUSTOMER
                        </small>


                        <h4 class="fw-bold mb-1">

                            <?= htmlspecialchars(
                                $order["first_name"] . " " .
                                $order["last_name"]
                            ) ?>

                        </h4>


                        <div class="text-muted">

                            <i class="bi bi-envelope"></i>

                            <?= htmlspecialchars(
                                $order["email"]
                            ) ?>

                        </div>

                    </div>


                    <div class="text-end">

                        <small class="text-muted">
                            ORDER
                        </small>


                        <h4 class="fw-bold mb-1">

                            #<?= (int)$order["order_id"] ?>

                        </h4>


                        <small class="text-muted">

                            <?= date(
                                "F d, Y h:i A",
                                strtotime($order["created_at"])
                            ) ?>

                        </small>

                    </div>

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
             ORDERED PRODUCTS
        ================================================== -->

        <div class="card tracking-card mb-4">

            <div class="card-body p-4">

                <h4 class="fw-bold mb-4">

                    <i class="bi bi-box-seam"></i>

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
                    class="d-flex justify-content-between pt-3"
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

    <div class="track-navigation">

        <a
            href="customer-orders.php?user_id=<?= (int)($order["user_id"] ?? 0) ?>"
            class="track-outline-btn"
        >

            <i class="bi bi-arrow-left"></i>

            Back to Customer Orders

        </a>


        <a
            href="admin-dashboard.php"
            class="track-primary-btn"
        >

            <i class="bi bi-people"></i>

            Customers

        </a>

    </div>


</div>


</body>

</html>