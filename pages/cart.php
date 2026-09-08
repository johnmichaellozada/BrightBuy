<?php

/**
 * BrightBuy Customer Cart
 *
 * Displays the customer's cart.
 *
 * CRUD operations are processed by:
 *
 * ../actions/cart.php
 */

session_start();

require_once "../db.php";


/* =========================================================
   CUSTOMER LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}


/* =========================================================
   GET CUSTOMER ID
========================================================= */

$user_id = (int) ($_SESSION["user_id"] ?? 0);

if ($user_id <= 0) {
    header("Location: login.php");
    exit;
}


/* =========================================================
   GET CUSTOMER CART
========================================================= */

$cartStmt = $pdo->prepare("
    SELECT cart_id
    FROM cart
    WHERE user_id = ?
    LIMIT 1
");

$cartStmt->execute([
    $user_id
]);

$cart = $cartStmt->fetch();


/* =========================================================
   DEFAULT VALUES
========================================================= */

$items = [];

$total = 0;


/* =========================================================
   GET CART ITEMS
========================================================= */

if ($cart) {

    $cart_id = (int) $cart["cart_id"];


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

        ORDER BY ci.cart_item_id DESC
    ");

    $itemsStmt->execute([
        $cart_id
    ]);

    $items = $itemsStmt->fetchAll();


    /* =====================================================
       CALCULATE TOTAL
    ===================================================== */

    foreach ($items as $item) {

        $total +=
            (float) $item["price"] *
            (int) $item["quantity"];
    }
}


/* =========================================================
   MESSAGES
========================================================= */

$success = $_GET["success"] ?? "";
$error = $_GET["error"] ?? "";

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


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >


    <!-- BrightBuy CSS -->

    <link
        rel="stylesheet"
        href="../css/styles.css"
    >

</head>


<body>


<!-- =========================================================
     HEADER
========================================================= -->

<div class="container py-4">

    <div
        class="d-flex justify-content-between align-items-center"
    >

        <div>

            <h2 class="fw-bold mb-1">

                <i class="bi bi-cart3"></i>

                My Cart

            </h2>

            <p class="text-muted mb-0">

                Review and manage your items.

            </p>

        </div>


        <a
            href="shop.php"
            class="btn btn-outline-primary"
        >

            <i class="bi bi-arrow-left"></i>

            Continue Shopping

        </a>

    </div>

</div>


<!-- =========================================================
     SUCCESS / ERROR MESSAGES
========================================================= -->

<div class="container">

    <?php if ($success === "added"): ?>

        <div class="alert alert-success">

            <i class="bi bi-check-circle-fill"></i>

            Product added to your cart.

        </div>

    <?php endif; ?>


    <?php if ($success === "updated"): ?>

        <div class="alert alert-success">

            <i class="bi bi-check-circle-fill"></i>

            Cart quantity updated.

        </div>

    <?php endif; ?>


    <?php if ($success === "removed"): ?>

        <div class="alert alert-success">

            <i class="bi bi-check-circle-fill"></i>

            Product removed from your cart.

        </div>

    <?php endif; ?>


    <?php if ($success === "cleared"): ?>

        <div class="alert alert-success">

            <i class="bi bi-check-circle-fill"></i>

            Your cart has been cleared.

        </div>

    <?php endif; ?>


    <?php if ($error === "item_not_found"): ?>

        <div class="alert alert-danger">

            Cart item could not be found.

        </div>

    <?php endif; ?>


    <?php if ($error === "out_of_stock"): ?>

        <div class="alert alert-danger">

            This product is currently out of stock.

        </div>

    <?php endif; ?>

</div>


<!-- =========================================================
     CART CONTENT
========================================================= -->

<div class="container py-4">


<?php if (empty($items)): ?>


    <!-- =====================================================
         EMPTY CART
    ====================================================== -->

    <div class="text-center py-5">

        <i
            class="bi bi-cart-x"
            style="font-size: 80px;"
        ></i>


        <h3 class="fw-bold mt-4">

            Your Cart is Empty

        </h3>


        <p class="text-muted">

            You haven't added any products yet.

        </p>


        <a
            href="shop.php"
            class="btn btn-primary px-4"
        >

            <i class="bi bi-bag"></i>

            Start Shopping

        </a>

    </div>


<?php else: ?>


    <div class="row g-4">


        <!-- =================================================
             CART ITEMS
        ================================================== -->

        <div class="col-lg-8">


            <?php foreach ($items as $item): ?>


                <div class="card shadow-sm border-0 mb-3">

                    <div class="card-body">

                        <div class="row align-items-center">


                            <!-- PRODUCT IMAGE -->

                            <div class="col-md-2 text-center">

                                <?php if (!empty($item["image"])): ?>

                                    <img
                                        src="../<?= htmlspecialchars($item["image"]) ?>"
                                        alt="<?= htmlspecialchars($item["product_name"]) ?>"
                                        class="img-fluid"
                                        style="
                                            width:90px;
                                            height:90px;
                                            object-fit:contain;
                                        "
                                    >

                                <?php else: ?>

                                    <i
                                        class="bi bi-image"
                                        style="font-size:60px;"
                                    ></i>

                                <?php endif; ?>

                            </div>


                            <!-- PRODUCT INFORMATION -->

                            <div class="col-md-4">

                                <h5 class="fw-bold">

                                    <?= htmlspecialchars(
                                        $item["product_name"]
                                    ) ?>

                                </h5>


                                <p class="text-muted mb-1">

                                    ₱<?= number_format(
                                        (float) $item["price"],
                                        2
                                    ) ?>

                                </p>


                                <small class="text-muted">

                                    Stock:
                                    <?= (int) $item["stock"] ?>

                                </small>

                            </div>


                            <!-- UPDATE QUANTITY -->

                            <div class="col-md-3">

                                <form
                                    method="POST"
                                    action="../actions/cart.php"
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="update"
                                    >


                                    <input
                                        type="hidden"
                                        name="cart_item_id"
                                        value="<?= (int) $item["cart_item_id"] ?>"
                                    >


                                    <label class="form-label small">

                                        Quantity

                                    </label>


                                    <div class="input-group">

                                        <input
                                            type="number"
                                            name="quantity"
                                            class="form-control"
                                            min="1"
                                            max="<?= (int) $item["stock"] ?>"
                                            value="<?= (int) $item["quantity"] ?>"
                                            required
                                        >


                                        <button
                                            type="submit"
                                            class="btn btn-outline-primary"
                                            title="Update Quantity"
                                        >

                                            <i class="bi bi-arrow-repeat"></i>

                                        </button>

                                    </div>

                                </form>

                            </div>


                            <!-- SUBTOTAL -->

                            <div
                                class="col-md-3 text-md-end mt-3 mt-md-0"
                            >

                                <strong class="d-block mb-3">

                                    ₱<?= number_format(
                                        (float) $item["price"] *
                                        (int) $item["quantity"],
                                        2
                                    ) ?>

                                </strong>


                                <!-- REMOVE -->

                                <form
                                    method="POST"
                                    action="../actions/cart.php"
                                    onsubmit="
                                        return confirm(
                                            'Remove this product from your cart?'
                                        );
                                    "
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="remove"
                                    >


                                    <input
                                        type="hidden"
                                        name="cart_item_id"
                                        value="<?= (int) $item["cart_item_id"] ?>"
                                    >


                                    <button
                                        type="submit"
                                        class="btn btn-outline-danger btn-sm"
                                    >

                                        <i class="bi bi-trash"></i>

                                        Remove

                                    </button>

                                </form>

                            </div>

                        </div>

                    </div>

                </div>


            <?php endforeach; ?>


            <!-- =================================================
                 CLEAR CART
            ================================================== -->

            <form
                method="POST"
                action="../actions/cart.php"
                onsubmit="
                    return confirm(
                        'Are you sure you want to clear your cart?'
                    );
                "
            >

                <input
                    type="hidden"
                    name="action"
                    value="clear"
                >


                <button
                    type="submit"
                    class="btn btn-outline-danger"
                >

                    <i class="bi bi-trash3"></i>

                    Clear Cart

                </button>

            </form>


        </div>


        <!-- =================================================
             ORDER SUMMARY
        ================================================== -->

        <div class="col-lg-4">

            <div class="card shadow-sm border-0">

                <div class="card-body p-4">

                    <h4 class="fw-bold mb-4">

                        Order Summary

                    </h4>


                    <div
                        class="
                            d-flex
                            justify-content-between
                            mb-3
                        "
                    >

                        <span>

                            Items

                        </span>


                        <span>

                            <?= count($items) ?>

                        </span>

                    </div>


                    <hr>


                    <div
                        class="
                            d-flex
                            justify-content-between
                            mb-4
                        "
                    >

                        <strong>

                            Total

                        </strong>


                        <strong class="fs-4">

                            ₱<?= number_format(
                                $total,
                                2
                            ) ?>

                        </strong>

                    </div>


                    <a
                        href="checkout.php"
                        class="btn btn-primary w-100"
                    >

                        <i class="bi bi-credit-card"></i>

                        Proceed to Checkout

                    </a>

                </div>

            </div>

        </div>


    </div>


<?php endif; ?>


</div>


<!-- =========================================================
     BACK TO HOME
========================================================= -->

<div class="container text-center py-4">

    <a
        href="../index.php"
        class="btn btn-outline-secondary"
    >

        <i class="bi bi-house"></i>

        Back to BrightBuy

    </a>

</div>


</body>

</html>