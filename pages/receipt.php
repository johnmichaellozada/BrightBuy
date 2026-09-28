<?php

session_start();
require_once "../db.php";


/* =========================================================
   CUSTOMER LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true ||
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "customer"
) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];


/* =========================================================
   GET ORDER ID
========================================================= */

$order_id = filter_input(
    INPUT_GET,
    "order_id",
    FILTER_VALIDATE_INT
);

if (!$order_id) {
    header("Location: my-orders.php");
    exit;
}


/* =========================================================
   GET ORDER
   MUST BELONG TO CURRENT CUSTOMER
========================================================= */

$orderStmt = $pdo->prepare("
    SELECT
        o.order_id,
        o.user_id,
        o.total_amount,
        o.gcash_number,
        o.gcash_reference,
        o.status,
        o.created_at,

        u.first_name,
        u.last_name,
        u.email,
        u.phone

    FROM orders o

    INNER JOIN users u
        ON o.user_id = u.user_id

    WHERE o.order_id = ?
      AND o.user_id = ?

    LIMIT 1
");

$orderStmt->execute([
    $order_id,
    $user_id
]);

$order = $orderStmt->fetch(PDO::FETCH_ASSOC);


/* =========================================================
   ORDER NOT FOUND
========================================================= */

if (!$order) {

    die("
        <!DOCTYPE html>
        <html>
        <head>
            <title>Receipt Not Found</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background: #f5f7fb;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    margin: 0;
                }

                .error-box {
                    background: white;
                    padding: 40px;
                    border-radius: 15px;
                    text-align: center;
                    box-shadow: 0 5px 25px rgba(0,0,0,.08);
                }

                h2 {
                    color: #082b73;
                }

                a {
                    display: inline-block;
                    margin-top: 15px;
                    padding: 10px 18px;
                    background: #0d47a1;
                    color: white;
                    text-decoration: none;
                    border-radius: 8px;
                }
            </style>
        </head>

        <body>

            <div class='error-box'>

                <h2>Receipt Not Found</h2>

                <p>
                    The requested order could not be found.
                </p>

                <a href='my-orders.php'>
                    Back to My Orders
                </a>

            </div>

        </body>
        </html>
    ");

    exit;
}


/* =========================================================
   IMPORTANT:
   RECEIPT ONLY AVAILABLE FOR DELIVERED ORDERS
========================================================= */

$orderStatus = strtolower(
    trim($order["status"] ?? "")
);

if ($orderStatus !== "delivered") {

    die("
        <!DOCTYPE html>
        <html>
        <head>
            <title>Receipt Unavailable</title>

            <style>

                body {
                    font-family: Arial, sans-serif;
                    background: #f5f7fb;

                    display: flex;
                    align-items: center;
                    justify-content: center;

                    min-height: 100vh;

                    margin: 0;
                }

                .error-box {

                    background: white;

                    width: 90%;
                    max-width: 500px;

                    padding: 40px;

                    border-radius: 15px;

                    text-align: center;

                    box-shadow:
                        0 5px 25px rgba(0,0,0,.08);
                }

                .error-icon {

                    width: 70px;
                    height: 70px;

                    margin: 0 auto 20px;

                    border-radius: 50%;

                    background: #fff3cd;

                    color: #856404;

                    display: flex;

                    align-items: center;
                    justify-content: center;

                    font-size: 32px;
                }

                h2 {

                    color: #082b73;

                    margin-bottom: 10px;
                }

                p {

                    color: #6b7280;

                    line-height: 1.6;
                }

                .status {

                    display: inline-block;

                    margin-top: 5px;

                    padding: 7px 14px;

                    border-radius: 20px;

                    background: #e8f0fe;

                    color: #0d47a1;

                    font-weight: 700;
                }

                a {

                    display: inline-block;

                    margin-top: 20px;

                    padding: 11px 20px;

                    background: #0d47a1;

                    color: white;

                    text-decoration: none;

                    border-radius: 8px;

                    font-weight: 600;
                }

            </style>

        </head>

        <body>

            <div class='error-box'>

                <div class='error-icon'>
                    ✓
                </div>

                <h2>
                    Receipt Not Available Yet
                </h2>

                <p>
                    A receipt can only be printed after
                    your order has been completed and
                    marked as <strong>Delivered</strong>.
                </p>

                <div class='status'>
                    Current Status:
                    " . htmlspecialchars($order["status"]) . "
                </div>

                <br>

                <a href='my-orders.php'>
                    Back to My Orders
                </a>

            </div>

        </body>
        </html>
    ");

    exit;
}


/* =========================================================
   GET ORDER ITEMS
========================================================= */

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

    ORDER BY oi.order_item_id ASC
");

$itemStmt->execute([
    $order_id
]);

$orderItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   GET DELIVERY ADDRESS
========================================================= */

$address = null;


/*
    Your orders currently store address_id.
    We retrieve the customer's address associated
    with the order when available.
*/

$addressStmt = $pdo->prepare("
    SELECT
        a.address_id,
        a.recipient_name,
        a.phone,
        a.address_line,
        a.barangay,
        a.city,
        a.province,
        a.postal_code

    FROM addresses a

    INNER JOIN orders o
        ON o.address_id = a.address_id

    WHERE o.order_id = ?
      AND o.user_id = ?

    LIMIT 1
");

$addressStmt->execute([
    $order_id,
    $user_id
]);

$address = $addressStmt->fetch(PDO::FETCH_ASSOC);


/* =========================================================
   PAYMENT METHOD
========================================================= */

$isGcash =
    !empty($order["gcash_number"]) ||
    !empty($order["gcash_reference"]);


/* =========================================================
   RECEIPT NUMBER
========================================================= */

$receiptNumber =
    "BB-" .
    str_pad(
        (string)$order["order_id"],
        6,
        "0",
        STR_PAD_LEFT
    );

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
        Receipt #<?= htmlspecialchars($receiptNumber) ?> |
        BrightBuy
    </title>


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


    <!-- BrightBuy CSS -->

    <link
        rel="stylesheet"
        href="../css/styles.css"
    >

</head>


<body class="receipt-page">


<!-- =========================================================
     RECEIPT ACTION BAR
========================================================= -->

<div class="receipt-actions">

    <a
        href="my-orders.php"
        class="receipt-back-btn"
    >

        <i class="bi bi-arrow-left"></i>

        Back to My Orders

    </a>


    <button
        type="button"
        class="receipt-print-btn"
        onclick="window.print()"
    >

        <i class="bi bi-printer-fill"></i>

        Print Receipt

    </button>

</div>


<!-- =========================================================
     RECEIPT
========================================================= -->

<main class="receipt-wrapper">


    <section class="receipt-card">


        <!-- =================================================
             RECEIPT HEADER
        ================================================== -->

        <div class="receipt-header">

            <div class="receipt-brand">

                <div class="receipt-logo">

                    <i class="bi bi-bag-check-fill"></i>

                </div>

                <div>

                    <h1>
                        BrightBuy
                    </h1>

                    <p>
                        Quality Products. Better Shopping.
                    </p>

                </div>

            </div>


            <div class="receipt-title">

                <span>
                    OFFICIAL RECEIPT
                </span>

                <strong>
                    #<?= htmlspecialchars($receiptNumber) ?>
                </strong>

            </div>

        </div>


        <!-- =================================================
             COMPLETED BADGE
        ================================================== -->

        <div class="receipt-completed">

            <i class="bi bi-check-circle-fill"></i>

            Order Completed

            <span>
                Delivered
            </span>

        </div>


        <!-- =================================================
             ORDER INFORMATION
        ================================================== -->

        <div class="receipt-info-grid">


            <div class="receipt-info-box">

                <span>
                    Order Number
                </span>

                <strong>
                    #<?= (int)$order["order_id"] ?>
                </strong>

            </div>


            <div class="receipt-info-box">

                <span>
                    Order Date
                </span>

                <strong>

                    <?= date(
                        "F d, Y",
                        strtotime($order["created_at"])
                    ) ?>

                </strong>

            </div>


            <div class="receipt-info-box">

                <span>
                    Completed Date
                </span>

                <strong>

                    <?= date(
                        "F d, Y",
                        strtotime($order["created_at"])
                    ) ?>

                </strong>

            </div>


            <div class="receipt-info-box">

                <span>
                    Payment Method
                </span>

                <strong>

                    <?= $isGcash
                        ? "GCash"
                        : "Cash on Delivery"
                    ?>

                </strong>

            </div>


        </div>


        <!-- =================================================
             CUSTOMER + DELIVERY
        ================================================== -->

        <div class="receipt-two-column">


            <!-- CUSTOMER -->

            <div class="receipt-section">

                <div class="receipt-section-heading">

                    <i class="bi bi-person-fill"></i>

                    Customer Information

                </div>


                <div class="receipt-section-content">

                    <strong>

                        <?= htmlspecialchars(
                            $order["first_name"]
                            . " "
                            . $order["last_name"]
                        ) ?>

                    </strong>


                    <span>

                        <i class="bi bi-envelope"></i>

                        <?= htmlspecialchars(
                            $order["email"]
                        ) ?>

                    </span>


                    <?php if (!empty($order["phone"])): ?>

                        <span>

                            <i class="bi bi-telephone"></i>

                            <?= htmlspecialchars(
                                $order["phone"]
                            ) ?>

                        </span>

                    <?php endif; ?>

                </div>

            </div>


            <!-- DELIVERY -->

            <div class="receipt-section">

                <div class="receipt-section-heading">

                    <i class="bi bi-geo-alt-fill"></i>

                    Delivery Address

                </div>


                <div class="receipt-section-content">

                    <?php if ($address): ?>

                        <strong>

                            <?= htmlspecialchars(
                                $address["recipient_name"]
                            ) ?>

                        </strong>


                        <?php if (!empty($address["phone"])): ?>

                            <span>

                                <i class="bi bi-telephone"></i>

                                <?= htmlspecialchars(
                                    $address["phone"]
                                ) ?>

                            </span>

                        <?php endif; ?>


                        <span>

                            <?= htmlspecialchars(
                                $address["address_line"]
                            ) ?>

                        </span>


                        <span>

                            <?= htmlspecialchars(
                                $address["barangay"]
                            ) ?>,

                            <?= htmlspecialchars(
                                $address["city"]
                            ) ?>

                        </span>


                        <span>

                            <?= htmlspecialchars(
                                $address["province"]
                            ) ?>

                            <?php if (
                                !empty($address["postal_code"])
                            ): ?>

                                ,

                                <?= htmlspecialchars(
                                    $address["postal_code"]
                                ) ?>

                            <?php endif; ?>

                        </span>

                    <?php else: ?>

                        <span>
                            Delivery address information unavailable.
                        </span>

                    <?php endif; ?>

                </div>

            </div>

        </div>


        <!-- =================================================
             PRODUCTS
        ================================================== -->

        <div class="receipt-products-section">


            <div class="receipt-section-heading">

                <i class="bi bi-box-seam-fill"></i>

                Order Items

            </div>


            <div class="receipt-table-wrapper">

                <table class="receipt-table">

                    <thead>

                        <tr>

                            <th>
                                Product
                            </th>

                            <th class="text-center">
                                Qty
                            </th>

                            <th class="text-end">
                                Unit Price
                            </th>

                            <th class="text-end">
                                Subtotal
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($orderItems as $item): ?>

                        <tr>

                            <td>

                                <div class="receipt-product">

                                    <div class="receipt-product-image">

                                        <?php if (
                                            !empty($item["image"])
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

                                            <i class="bi bi-image"></i>

                                        <?php endif; ?>

                                    </div>


                                    <span>

                                        <?= htmlspecialchars(
                                            $item["product_name"]
                                        ) ?>

                                    </span>

                                </div>

                            </td>


                            <td class="text-center">

                                <?= (int)$item["quantity"] ?>

                            </td>


                            <td class="text-end">

                                ₱<?= number_format(
                                    $item["price"],
                                    2
                                ) ?>

                            </td>


                            <td class="text-end fw-bold">

                                ₱<?= number_format(
                                    $item["subtotal"],
                                    2
                                ) ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>


        <!-- =================================================
             PAYMENT DETAILS
        ================================================== -->

        <div class="receipt-payment-section">


            <div class="receipt-section-heading">

                <i class="bi bi-credit-card-fill"></i>

                Payment Details

            </div>


            <div class="receipt-payment-details">


                <div>

                    <span>
                        Payment Method
                    </span>

                    <strong>

                        <?= $isGcash
                            ? "GCash"
                            : "Cash on Delivery"
                        ?>

                    </strong>

                </div>


                <?php if ($isGcash): ?>


                    <?php if (
                        !empty($order["gcash_number"])
                    ): ?>

                        <div>

                            <span>
                                GCash Number
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $order["gcash_number"]
                                ) ?>

                            </strong>

                        </div>

                    <?php endif; ?>


                    <?php if (
                        !empty($order["gcash_reference"])
                    ): ?>

                        <div>

                            <span>
                                Reference Number
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $order["gcash_reference"]
                                ) ?>

                            </strong>

                        </div>

                    <?php endif; ?>


                <?php else: ?>

                    <div>

                        <span>
                            Payment Status
                        </span>

                        <strong>
                            Pay on Delivery
                        </strong>

                    </div>

                <?php endif; ?>


            </div>

        </div>


        <!-- =================================================
             TOTAL
        ================================================== -->

        <div class="receipt-total-section">

            <span>
                TOTAL PAID / ORDER TOTAL
            </span>

            <strong>

                ₱<?= number_format(
                    $order["total_amount"],
                    2
                ) ?>

            </strong>

        </div>


        <!-- =================================================
             FOOTER
        ================================================== -->

        <div class="receipt-footer">

            <div>

                <i class="bi bi-check-circle-fill"></i>

                Thank you for shopping with BrightBuy!

            </div>

            <p>

                This receipt confirms that your order has
                been completed and delivered.

            </p>

        </div>


    </section>

</main>


</body>
</html>