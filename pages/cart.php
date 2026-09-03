<?php

session_start();
require_once "../db.php";

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

/* Get user's cart */
$cartStmt = $pdo->prepare("
    SELECT cart_id
    FROM cart
    WHERE user_id = ?
    LIMIT 1
");

$cartStmt->execute([$user_id]);

$cart = $cartStmt->fetch();

$cartItems = [];
$subtotal = 0;

if ($cart) {

    $cart_id = $cart["cart_id"];

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
        ORDER BY ci.created_at DESC
    ");

    $itemsStmt->execute([$cart_id]);

    $cartItems = $itemsStmt->fetchAll();

    foreach ($cartItems as &$item) {
        $item["subtotal"] =
            $item["price"] * $item["quantity"];

        $subtotal += $item["subtotal"];
    }

    unset($item);
}

$total = $subtotal;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>BrightBuy | Shopping Cart</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../css/styles.css"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

</head>

<body>

<div class="container py-5">

    <h1 class="mb-4">
        <i class="bi bi-cart3"></i>
        My Shopping Cart
    </h1>

    <?php if (empty($cartItems)): ?>

        <div class="text-center py-5">

            <i
                class="bi bi-cart-x"
                style="font-size: 70px;"
            ></i>

            <h3 class="mt-3">
                Your cart is empty
            </h3>

            <p class="text-muted">
                Add some products to your cart.
            </p>

            <a
                href="../index.php"
                class="btn btn-primary"
            >
                Continue Shopping
            </a>

        </div>

    <?php else: ?>

        <div class="row g-4">

            <div class="col-lg-8">

                <?php foreach ($cartItems as $item): ?>

                    <div class="card mb-3 shadow-sm">

                        <div class="card-body">

                            <div class="row align-items-center">

                                <div class="col-md-2">

                                    <img
                                        src="../<?= htmlspecialchars($item["image"]) ?>"
                                        alt="<?= htmlspecialchars($item["product_name"]) ?>"
                                        class="img-fluid"
                                    >

                                </div>

                                <div class="col-md-4">

                                    <h5>
                                        <?= htmlspecialchars($item["product_name"]) ?>
                                        <div class="mt-1">

    <?php if ((int)$item["stock"] > 10): ?>

        <small class="text-success fw-semibold">
            <i class="bi bi-check-circle-fill"></i>
            <?= (int)$item["stock"] ?> available
        </small>

    <?php elseif ((int)$item["stock"] > 0): ?>

        <small class="text-warning fw-semibold">
            <i class="bi bi-exclamation-circle-fill"></i>
            Only <?= (int)$item["stock"] ?> left
        </small>

    <?php else: ?>

        <small class="text-danger fw-semibold">
            <i class="bi bi-x-circle-fill"></i>
            Out of Stock
        </small>

    <?php endif; ?>

</div>
                                    </h5>

                                    <p class="text-muted mb-0">
                                        ₱<?= number_format($item["price"], 2) ?>
                                    </p>

                                </div>

                                <div class="col-md-2">

                                    <div class="cart-quantity">

    <form method="POST" action="update-cart.php">
        
        <input
            type="hidden"
            name="cart_item_id"
            value="<?= (int)$item["cart_item_id"] ?>"
        >

        <input
            type="hidden"
            name="quantity"
            value="<?= max(1, (int)$item["quantity"] - 1) ?>"
        >

        <button
            type="submit"
            class="quantity-btn"
            aria-label="Decrease quantity"
        >
            −
        </button>

    </form>

    <span class="quantity-number">
        <?= (int)$item["quantity"] ?>
    </span>

    <form method="POST" action="update-cart.php">

        <input
            type="hidden"
            name="cart_item_id"
            value="<?= (int)$item["cart_item_id"] ?>"
        >

        <input
            type="hidden"
            name="quantity"
            value="<?= (int)$item["quantity"] + 1 ?>"
        >

        <button
    type="submit"
    class="quantity-btn"
    aria-label="Increase quantity"
    <?= (int)$item["quantity"] >= (int)$item["stock"] ? "disabled" : "" ?>
>
    +
</button>

    </form>

</div>

                                </div>

                                <div class="col-md-2">

                                    <strong>
                                        ₱<?= number_format($item["subtotal"], 2) ?>
                                    </strong>

                                </div>

                                <div class="col-md-2">

                                    <a
                                        href="remove-cart-item.php?id=<?= (int)$item["cart_item_id"] ?>"
                                        class="btn btn-outline-danger btn-sm"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <div class="col-lg-4">

                <div class="card shadow-sm">

                    <div class="card-body">

                        <h4>
                            Order Summary
                        </h4>

                        <hr>

                        <div class="d-flex justify-content-between mb-3">

                            <span>
                                Subtotal
                            </span>

                            <strong>
                                ₱<?= number_format($subtotal, 2) ?>
                            </strong>

                        </div>

                        <div class="d-flex justify-content-between mb-3">

                            <span>
                                Total
                            </span>

                            <strong>
                                ₱<?= number_format($total, 2) ?>
                            </strong>

                        </div>

                        <a href="checkout.php" class="btn btn-primary">
    <i class="bi bi-credit-card"></i>
    Proceed to Checkout
</a>

                    </div>

                </div>

            </div>

        </div>

 <?php endif; ?>

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