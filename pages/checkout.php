<?php

session_start();
require_once "../db.php";

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

/* =====================================================
   GET USER CART
===================================================== */

$cartStmt = $pdo->prepare("
    SELECT cart_id
    FROM cart
    WHERE user_id = ?
    LIMIT 1
");

$cartStmt->execute([$user_id]);

$cart = $cartStmt->fetch();

if (!$cart) {
    header("Location: cart.php");
    exit;
}

$cart_id = $cart["cart_id"];


/* =====================================================
   GET CART ITEMS
===================================================== */

$itemsStmt = $pdo->prepare("
    SELECT
        ci.cart_item_id,
        ci.product_id,
        ci.quantity,
        p.product_name,
        p.price,
        p.image,
        p.stock
    FROM cart_items ci
    INNER JOIN products p
        ON ci.product_id = p.product_id
    WHERE ci.cart_id = ?
");

$itemsStmt->execute([$cart_id]);

$items = $itemsStmt->fetchAll();

if (!$items) {
    header("Location: cart.php");
    exit;
}


/* =====================================================
   CALCULATE TOTAL
===================================================== */

$total = 0;
$stockError = false;

foreach ($items as $item) {

    $total += $item["price"] * $item["quantity"];

    if ((int)$item["quantity"] > (int)$item["stock"]) {
        $stockError = true;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout - BrightBuy</title>

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

    <div class="text-center mb-5">

        <h1 class="fw-bold">
            Checkout
        </h1>

        <p class="text-muted">
            Review your order before placing it.
        </p>

    </div>


    <div class="row g-4">

        <!-- =================================================
             ORDER SUMMARY
        ================================================== -->

        <div class="col-lg-7">

            <div class="card shadow-sm border-0">

                <div class="card-body p-4">

                    <h4 class="fw-bold mb-4">
                        <i class="bi bi-bag-check"></i>
                        Your Order
                    </h4>


                    <?php foreach ($items as $item): ?>

                        <div class="d-flex align-items-center border-bottom py-3">

                        <img
    src="../<?= htmlspecialchars($item["image"]) ?>"
    alt="<?= htmlspecialchars($item["product_name"]) ?>"
    width="80"
    height="80"
    style="object-fit: contain;"
    class="me-3"
>

                            <div class="flex-grow-1">

                                <h6 class="fw-bold mb-1">
    <?= htmlspecialchars($item["product_name"]) ?>
</h6>

<!-- PRODUCT STOCK -->

<?php if ((int)$item["stock"] > 10): ?>

    <small class="text-success fw-semibold d-block">
        <i class="bi bi-check-circle-fill"></i>
        <?= (int)$item["stock"] ?> available
    </small>

<?php elseif ((int)$item["stock"] > 0): ?>

    <small class="text-warning fw-semibold d-block">
        <i class="bi bi-exclamation-circle-fill"></i>
        Only <?= (int)$item["stock"] ?> left
    </small>

<?php else: ?>

    <small class="text-danger fw-semibold d-block">
        <i class="bi bi-x-circle-fill"></i>
        Out of Stock
    </small>

<?php endif; ?>

<!-- PRICE × QUANTITY -->

<small class="text-muted">
    ₱<?= number_format($item["price"], 2) ?>
    ×
    <?= (int)$item["quantity"] ?>
</small>

                            </div>

                            <strong>
                                ₱<?= number_format(
                                    $item["price"] * $item["quantity"],
                                    2
                                ) ?>
                            </strong>

                        </div>

                    <?php endforeach; ?>


                    <div class="d-flex justify-content-between mt-4">

                        <span class="fw-bold">
                            Total
                        </span>

                        <span class="fw-bold fs-4 text-primary">
                            ₱<?= number_format($total, 2) ?>
                        </span>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
     CHECKOUT INFORMATION
================================================== -->

<div class="col-lg-5">

    <div class="card shadow-sm border-0">

        <div class="card-body p-4">

            <h4 class="fw-bold mb-4">
                Delivery Information
            </h4>


            <form method="POST" action="place-order.php">


                <!-- Recipient Name -->

                <div class="mb-3">

                    <label class="form-label">
                        Recipient Name
                    </label>

                    <input
                        type="text"
                        name="recipient_name"
                        class="form-control"
                        value="<?= htmlspecialchars(
                            $_SESSION["first_name"] . " " .
                            $_SESSION["last_name"]
                        ) ?>"
                        required
                    >

                </div>


                <!-- Phone -->

                <div class="mb-3">

                    <label class="form-label">
                        Phone Number
                    </label>

                    <input
                        type="text"
                        name="phone"
                        class="form-control"
                        value="<?= htmlspecialchars(
                            $_SESSION["phone"] ?? ""
                        ) ?>"
                        placeholder="09XXXXXXXXX"
                        required
                    >

                </div>


                <!-- Address -->

                <div class="mb-3">

                    <label class="form-label">
                        Address
                    </label>

                    <textarea
                        name="address_line"
                        class="form-control"
                        rows="2"
                        placeholder="House/Building No., Street"
                        required
                    ></textarea>

                </div>


                <!-- Barangay -->

                <div class="mb-3">

                    <label class="form-label">
                        Barangay
                    </label>

                    <input
                        type="text"
                        name="barangay"
                        class="form-control"
                        placeholder="Enter barangay"
                        required
                    >

                </div>


                <!-- City -->

                <div class="mb-3">

                    <label class="form-label">
                        City / Municipality
                    </label>

                    <input
                        type="text"
                        name="city"
                        class="form-control"
                        placeholder="Enter city or municipality"
                        required
                    >

                </div>


                <!-- Province -->

                <div class="mb-3">

                    <label class="form-label">
                        Province
                    </label>

                    <input
                        type="text"
                        name="province"
                        class="form-control"
                        placeholder="Enter province"
                        required
                    >

                </div>


                <!-- Postal Code -->

                <div class="mb-3">

                    <label class="form-label">
                        Postal Code
                    </label>

                    <input
                        type="text"
                        name="postal_code"
                        class="form-control"
                        placeholder="Optional"
                    >

                </div>


                <!-- Payment Method -->

                <div class="mb-3">

                    <label class="form-label">
                        Payment Method
                    </label>

                    <select
                        name="payment_method"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Select payment method
                        </option>

                        <option value="cod">
                            Cash on Delivery
                        </option>

                        <option value="gcash">
                            GCash
                        </option>

                    </select>

                </div>


                <!-- Place Order -->

                <?php if ($stockError): ?>

    <div class="alert alert-danger mt-3">
        <i class="bi bi-exclamation-triangle-fill"></i>
        Some items in your cart do not have enough stock.
        Please go back to your cart and update your quantity.
    </div>

<?php endif; ?>

<button
    type="submit"
    class="btn btn-primary w-100 py-2"
    <?= $stockError ? "disabled" : "" ?>
>
    <i class="bi bi-check-circle"></i>
    Place Order
</button>


                <!-- Back to Cart -->

                <a
                    href="cart.php"
                    class="btn btn-outline-secondary w-100 mt-2"
                >

                    <i class="bi bi-arrow-left"></i>
                    Back to Cart

                </a>

            </form>

        </div>

    </div>

</div>

    <div class="text-center mt-4">

        <a
            href="../index.php"
            class="btn btn-outline-primary px-4"
        >
            <i class="bi bi-house"></i>
            Back to Home
        </a>

    </div>

</div>

</body>

</html>