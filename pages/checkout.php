<?php

session_start();
require_once "../db.php";

/* =====================================================
   LOGIN CHECK
===================================================== */

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}


/* =====================================================
   CUSTOMER ACCESS CHECK
===================================================== */

$userRoleStmt = $pdo->prepare("
    SELECT role
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$userRoleStmt->execute([
    $_SESSION["user_id"]
]);

$currentUser = $userRoleStmt->fetch(PDO::FETCH_ASSOC);

if (
    !$currentUser ||
    $currentUser["role"] !== "customer"
) {
    header("Location: ../index.php");
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

$cart = $cartStmt->fetch(PDO::FETCH_ASSOC);

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

$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

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

    $total +=
        (float) $item["price"] *
        (int) $item["quantity"];

    if (
        (int) $item["quantity"] >
        (int) $item["stock"]
    ) {
        $stockError = true;
    }
}


/* =====================================================
   GET SAVED ADDRESSES
===================================================== */

$addressStmt = $pdo->prepare("
    SELECT
        address_id,
        recipient_name,
        phone,
        address_line,
        barangay,
        city,
        province,
        postal_code,
        is_default
    FROM addresses
    WHERE user_id = ?
    ORDER BY
        is_default DESC,
        address_id DESC
");

$addressStmt->execute([$user_id]);

$savedAddresses = $addressStmt->fetchAll(PDO::FETCH_ASSOC);

$hasSavedAddress = !empty($savedAddresses);


/* =====================================================
   SELECT DEFAULT ADDRESS
===================================================== */

$defaultAddressId = null;

foreach ($savedAddresses as $address) {

    if ((int) $address["is_default"] === 1) {

        $defaultAddressId =
            (int) $address["address_id"];

        break;
    }
}


/* =====================================================
   IF NO DEFAULT ADDRESS
===================================================== */

if (
    $defaultAddressId === null &&
    $hasSavedAddress
) {
    $defaultAddressId =
        (int) $savedAddresses[0]["address_id"];
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

    <title>Checkout - BrightBuy</title>


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


    <!-- Main Styles -->
    <link
        rel="stylesheet"
        href="../css/styles.css"
    >

</head>


<body class="checkout-page">


<!-- =====================================================
     BACKGROUND DECORATION
===================================================== -->

<div class="checkout-bg-circle checkout-circle-one"></div>
<div class="checkout-bg-circle checkout-circle-two"></div>
<div class="checkout-bg-circle checkout-circle-three"></div>


<!-- =====================================================
     CHECKOUT HEADER
===================================================== -->

<header class="checkout-header">

    <div class="checkout-header-inner">

        <div class="checkout-header-icon">
            <i class="bi bi-bag-check-fill"></i>
        </div>

        <div class="checkout-header-text">

            <span class="checkout-header-label">
                BRIGHTBUY
            </span>

            <h1>
                Checkout
            </h1>

            <p>
                Review your order and delivery information.
            </p>

        </div>

    </div>

</header>


<!-- =====================================================
     MAIN CHECKOUT
===================================================== -->

<main class="checkout-container">


    <!-- PAGE INTRO -->

    <div class="checkout-page-intro">

        <div>

            <span class="checkout-small-title">
                COMPLETE YOUR PURCHASE
            </span>

            <h2>
                Almost there!
            </h2>

            <p>
                Check your order details before placing your order.
            </p>

        </div>

        <div class="checkout-secure-badge">

            <i class="bi bi-shield-check"></i>

            <div>
                <strong>Secure Checkout</strong>
                <span>Your information is protected</span>
            </div>

        </div>

    </div>


    <!-- =================================================
         CHECKOUT GRID
    ================================================== -->

    <div class="checkout-grid">


        <!-- =================================================
             ORDER SUMMARY
        ================================================== -->

        <section class="checkout-card order-card">

            <div class="checkout-card-header">

                <div class="checkout-card-icon">
                    <i class="bi bi-bag-check-fill"></i>
                </div>

                <div>

                    <span class="checkout-card-label">
                        ORDER SUMMARY
                    </span>

                    <h2>
                        Your Order
                    </h2>

                </div>

            </div>


            <div class="checkout-card-body">


                <!-- ORDER ITEMS -->

                <div class="order-items">

                    <?php foreach ($items as $item): ?>

                        <div class="order-item">


                            <!-- PRODUCT IMAGE -->

                            <div class="product-image-wrapper">

                                <?php
                                $imagePath = "../" . ltrim(
                                    $item["image"],
                                    "/"
                                );
                                ?>

                                <img
                                    src="<?= htmlspecialchars($imagePath) ?>"
                                    alt="<?= htmlspecialchars($item["product_name"]) ?>"
                                    class="checkout-product-image"
                                >

                            </div>


                            <!-- PRODUCT INFORMATION -->

                            <div class="product-information">

                                <h3>
                                    <?= htmlspecialchars(
                                        $item["product_name"]
                                    ) ?>
                                </h3>


                                <div class="product-price-quantity">

                                    ₱<?= number_format(
                                        (float) $item["price"],
                                        2
                                    ) ?>

                                    <span>×</span>

                                    <?= (int) $item["quantity"] ?>

                                </div>


                                <!-- STOCK -->

                                <?php if ((int) $item["stock"] > 10): ?>

                                    <div class="stock-available">

                                        <i class="bi bi-check-circle-fill"></i>

                                        <?= (int) $item["stock"] ?>
                                        available

                                    </div>

                                <?php elseif ((int) $item["stock"] > 0): ?>

                                    <div class="stock-low">

                                        <i class="bi bi-exclamation-circle-fill"></i>

                                        Only
                                        <?= (int) $item["stock"] ?>
                                        left

                                    </div>

                                <?php else: ?>

                                    <div class="stock-out">

                                        <i class="bi bi-x-circle-fill"></i>

                                        Out of Stock

                                    </div>

                                <?php endif; ?>

                            </div>


                            <!-- SUBTOTAL -->

                            <div class="product-subtotal">

                                <span>
                                    SUBTOTAL
                                </span>

                                <strong>

                                    ₱<?= number_format(
                                        (float) $item["price"] *
                                        (int) $item["quantity"],
                                        2
                                    ) ?>

                                </strong>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>


                <!-- TOTAL -->

                <div class="checkout-total">

                    <div>

                        <span>
                            Order Total
                        </span>

                        <small>
                            <?= count($items) ?>
                            <?= count($items) === 1 ? "item" : "items" ?>
                        </small>

                    </div>

                    <strong>
                        ₱<?= number_format($total, 2) ?>
                    </strong>

                </div>

            </div>

        </section>


        <!-- =================================================
             DELIVERY INFORMATION
        ================================================== -->

        <section class="checkout-card delivery-card">

            <div class="checkout-card-header">

                <div class="checkout-card-icon">

                    <i class="bi bi-geo-alt-fill"></i>

                </div>

                <div>

                    <span class="checkout-card-label">
                        DELIVERY
                    </span>

                    <h2>
                        Delivery Information
                    </h2>

                </div>

            </div>


            <div class="checkout-card-body">

                <form
                    method="POST"
                    action="place-order.php"
                    id="checkoutForm"
                >


                    <!-- =================================================
                         SAVED ADDRESSES
                    ================================================== -->

                    <?php if ($hasSavedAddress): ?>

                        <div class="address-container">

                            <label class="checkout-label">
                                Choose Delivery Address
                            </label>


                            <?php foreach ($savedAddresses as $address): ?>

                                <?php

                                $addressId =
                                    (int) $address["address_id"];

                                $isSelected =
                                    $addressId ===
                                    $defaultAddressId;

                                ?>

                                <label
                                    class="address-option <?= $isSelected ? "selected" : "" ?>"
                                >

                                    <input
                                        type="radio"
                                        name="address_option"
                                        value="saved"
                                        class="address-radio"
                                        data-address-id="<?= $addressId ?>"
                                        <?= $isSelected ? "checked" : "" ?>
                                    >


                                    <div class="address-radio-custom"></div>


                                    <div class="saved-address-icon">

                                        <i class="bi bi-house-door-fill"></i>

                                    </div>


                                    <div class="address-details">

                                        <div class="address-name-row">

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $address["recipient_name"]
                                                ) ?>
                                            </strong>


                                            <?php if (
                                                (int) $address["is_default"] === 1
                                            ): ?>

                                                <span class="default-badge">
                                                    DEFAULT
                                                </span>

                                            <?php endif; ?>

                                        </div>


                                        <div class="address-line">

                                            <i class="bi bi-telephone-fill"></i>

                                            <span>
                                                <?= htmlspecialchars(
                                                    $address["phone"]
                                                ) ?>
                                            </span>

                                        </div>


                                        <div class="address-line">

                                            <i class="bi bi-house-fill"></i>

                                            <span>
                                                <?= htmlspecialchars(
                                                    $address["address_line"]
                                                ) ?>
                                            </span>

                                        </div>


                                        <div class="address-line">

                                            <span>

                                                <?= htmlspecialchars(
                                                    $address["barangay"]
                                                ) ?>,

                                                <?= htmlspecialchars(
                                                    $address["city"]
                                                ) ?>

                                            </span>

                                        </div>


                                        <div class="address-line">

                                            <span>

                                                <?= htmlspecialchars(
                                                    $address["province"]
                                                ) ?>

                                                <?php if (
                                                    !empty(
                                                        $address["postal_code"]
                                                    )
                                                ): ?>

                                                    ,

                                                    <?= htmlspecialchars(
                                                        $address["postal_code"]
                                                    ) ?>

                                                <?php endif; ?>

                                            </span>

                                        </div>

                                    </div>

                                </label>

                            <?php endforeach; ?>


                            <input
                                type="hidden"
                                name="address_id"
                                id="selectedAddressId"
                                value="<?= $defaultAddressId ?>"
                            >


                            <!-- ADD NEW -->

                            <button
                                type="button"
                                class="new-address-button"
                                id="showNewAddress"
                            >

                                <i class="bi bi-plus-circle-fill"></i>

                                Add New Address

                            </button>

                        </div>


                        <!-- =================================================
                             NEW ADDRESS
                        ================================================== -->

                        <div
                            id="newAddressSection"
                            class="new-address-section"
                        >

                            <div class="new-address-heading">

                                <div class="new-address-icon">

                                    <i class="bi bi-plus-lg"></i>

                                </div>

                                <div>

                                    <h3>
                                        New Delivery Address
                                    </h3>

                                    <p>
                                        Enter a different address for this order.
                                    </p>

                                </div>

                            </div>


                            <!-- RECIPIENT -->

                            <div class="checkout-field">

                                <label for="recipient_name">

                                    Recipient Name

                                    <span>*</span>

                                </label>

                                <input
                                    type="text"
                                    id="recipient_name"
                                    name="recipient_name"
                                    class="checkout-input new-address-field"
                                    value="<?= htmlspecialchars(
                                        ($_SESSION["first_name"] ?? "") .
                                        " " .
                                        ($_SESSION["last_name"] ?? "")
                                    ) ?>"
                                    disabled
                                >

                            </div>


                            <!-- PHONE -->

                            <div class="checkout-field">

                                <label for="phone">

                                    Phone Number

                                    <span>*</span>

                                </label>

                                <input
                                    type="text"
                                    id="phone"
                                    name="phone"
                                    class="checkout-input new-address-field"
                                    value="<?= htmlspecialchars(
                                        $_SESSION["phone"] ?? ""
                                    ) ?>"
                                    placeholder="09XXXXXXXXX"
                                    maxlength="11"
                                    inputmode="numeric"
                                    disabled
                                >

                            </div>


                            <!-- ADDRESS -->

                            <div class="checkout-field">

                                <label for="address_line">

                                    House/Building No. & Street

                                    <span>*</span>

                                </label>

                                <textarea
                                    id="address_line"
                                    name="address_line"
                                    class="checkout-input new-address-field"
                                    rows="2"
                                    placeholder="e.g. Purok 1, National Highway"
                                    disabled
                                ></textarea>

                            </div>


                            <!-- BARANGAY -->

                            <div class="checkout-field">

                                <label for="barangay">

                                    Barangay

                                    <span>*</span>

                                </label>

                                <input
                                    type="text"
                                    id="barangay"
                                    name="barangay"
                                    class="checkout-input new-address-field"
                                    placeholder="Enter barangay"
                                    disabled
                                >

                            </div>


                            <!-- CITY -->

                            <div class="checkout-field">

                                <label for="city">

                                    City / Municipality

                                    <span>*</span>

                                </label>

                                <input
                                    type="text"
                                    id="city"
                                    name="city"
                                    class="checkout-input new-address-field"
                                    placeholder="Enter city or municipality"
                                    disabled
                                >

                            </div>


                            <!-- PROVINCE -->

                            <div class="checkout-field">

                                <label for="province">

                                    Province

                                    <span>*</span>

                                </label>

                                <input
                                    type="text"
                                    id="province"
                                    name="province"
                                    class="checkout-input new-address-field"
                                    placeholder="Enter province"
                                    disabled
                                >

                            </div>


                            <!-- POSTAL -->

                            <div class="checkout-field">

                                <label for="postal_code">

                                    Postal Code

                                </label>

                                <input
                                    type="text"
                                    id="postal_code"
                                    name="postal_code"
                                    class="checkout-input new-address-field"
                                    placeholder="Optional"
                                    disabled
                                >

                            </div>


                            <!-- DEFAULT -->

                            <div class="default-checkbox">

                                <input
                                    type="checkbox"
                                    name="save_as_default"
                                    value="1"
                                    id="saveAsDefault"
                                    class="new-address-field"
                                    disabled
                                >

                                <label for="saveAsDefault">

                                    Make this my default address

                                </label>

                            </div>


                            <!-- CANCEL NEW ADDRESS -->

                            <button
                                type="button"
                                class="cancel-address-button"
                                id="cancelNewAddress"
                            >

                                <i class="bi bi-arrow-left"></i>

                                Use Saved Address

                            </button>

                        </div>


                    <?php else: ?>


                        <!-- =================================================
                             NO SAVED ADDRESS
                        ================================================== -->

                        <div class="no-address-message">

                            <div class="no-address-icon">

                                <i class="bi bi-info-circle-fill"></i>

                            </div>

                            <div>

                                <strong>
                                    No saved address
                                </strong>

                                <span>
                                    Please enter your delivery address below.
                                </span>

                            </div>

                        </div>


                        <!-- RECIPIENT -->

                        <div class="checkout-field">

                            <label for="recipient_name">

                                Recipient Name

                                <span>*</span>

                            </label>

                            <input
                                type="text"
                                id="recipient_name"
                                name="recipient_name"
                                class="checkout-input"
                                value="<?= htmlspecialchars(
                                    ($_SESSION["first_name"] ?? "") .
                                    " " .
                                    ($_SESSION["last_name"] ?? "")
                                ) ?>"
                                required
                            >

                        </div>


                        <!-- PHONE -->

                        <div class="checkout-field">

                            <label for="phone">

                                Phone Number

                                <span>*</span>

                            </label>

                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                class="checkout-input"
                                value="<?= htmlspecialchars(
                                    $_SESSION["phone"] ?? ""
                                ) ?>"
                                placeholder="09XXXXXXXXX"
                                maxlength="11"
                                inputmode="numeric"
                                required
                            >

                        </div>


                        <!-- ADDRESS -->

                        <div class="checkout-field">

                            <label for="address_line">

                                House/Building No. & Street

                                <span>*</span>

                            </label>

                            <textarea
                                id="address_line"
                                name="address_line"
                                class="checkout-input"
                                rows="2"
                                placeholder="e.g. Purok 1, National Highway"
                                required
                            ></textarea>

                        </div>


                        <!-- BARANGAY -->

                        <div class="checkout-field">

                            <label for="barangay">

                                Barangay

                                <span>*</span>

                            </label>

                            <input
                                type="text"
                                id="barangay"
                                name="barangay"
                                class="checkout-input"
                                placeholder="Enter barangay"
                                required
                            >

                        </div>


                        <!-- CITY -->

                        <div class="checkout-field">

                            <label for="city">

                                City / Municipality

                                <span>*</span>

                            </label>

                            <input
                                type="text"
                                id="city"
                                name="city"
                                class="checkout-input"
                                placeholder="Enter city or municipality"
                                required
                            >

                        </div>


                        <!-- PROVINCE -->

                        <div class="checkout-field">

                            <label for="province">

                                Province

                                <span>*</span>

                            </label>

                            <input
                                type="text"
                                id="province"
                                name="province"
                                class="checkout-input"
                                placeholder="Enter province"
                                required
                            >

                        </div>


                        <!-- POSTAL -->

                        <div class="checkout-field">

                            <label for="postal_code">

                                Postal Code

                            </label>

                            <input
                                type="text"
                                id="postal_code"
                                name="postal_code"
                                class="checkout-input"
                                placeholder="Optional"
                            >

                        </div>


                        <!-- DEFAULT -->

                        <div class="default-checkbox">

                            <input
                                type="checkbox"
                                name="save_as_default"
                                value="1"
                                id="saveAsDefaultNoAddress"
                            >

                            <label for="saveAsDefaultNoAddress">

                                Make this my default address

                            </label>

                        </div>

                    <?php endif; ?>


                    <!-- =================================================
                         PAYMENT METHOD
                    ================================================== -->

                    <div class="payment-section">

                        <div class="payment-section-title">

                            <div class="payment-icon">

                                <i class="bi bi-wallet2"></i>

                            </div>

                            <div>

                                <span>
                                    PAYMENT
                                </span>

                                <h3>
                                    Payment Method
                                </h3>

                            </div>

                        </div>


                        <div class="checkout-field">

                            <label for="paymentMethod">

                                Select Payment Method

                                <span>*</span>

                            </label>

                            <select
                                name="payment_method"
                                id="paymentMethod"
                                class="checkout-input checkout-select"
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

                    </div>


                    <!-- =================================================
                         GCASH PAYMENT
                    ================================================== -->

                    <div
                        id="gcashPaymentSection"
                        class="gcash-payment-section"
                    >

                        <div class="gcash-top">

                            <div class="gcash-logo">

                                <i class="bi bi-phone-fill"></i>

                            </div>

                            <div>

                                <span>
                                    MOBILE PAYMENT
                                </span>

                                <h3>
                                    GCash Payment
                                </h3>

                                <p>
                                    Enter the GCash details used for your payment.
                                </p>

                            </div>

                        </div>


                        <div class="gcash-details-box">

                            <!-- GCASH NUMBER -->

                            <div class="checkout-field">

                                <label for="gcashNumber">

                                    GCash Number

                                    <span>*</span>

                                </label>

                                <div class="input-with-icon">

                                    <i class="bi bi-phone"></i>

                                    <input
                                        type="text"
                                        name="gcash_number"
                                        id="gcashNumber"
                                        class="checkout-input"
                                        placeholder="09XXXXXXXXX"
                                        maxlength="11"
                                        minlength="11"
                                        inputmode="numeric"
                                        autocomplete="tel"
                                    >

                                </div>

                                <small>
                                    Enter the 11-digit GCash mobile number used for payment.
                                </small>

                            </div>


                            <!-- REFERENCE -->

                            <div class="checkout-field">

                                <label for="gcashReference">

                                    GCash Reference Number

                                    <span>*</span>

                                </label>

                                <div class="input-with-icon">

                                    <i class="bi bi-receipt"></i>

                                    <input
                                        type="text"
                                        name="gcash_reference"
                                        id="gcashReference"
                                        class="checkout-input"
                                        placeholder="Enter GCash reference number"
                                        maxlength="100"
                                        autocomplete="off"
                                    >

                                </div>

                                <small>
                                    Enter the reference number shown after your GCash payment.
                                </small>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         STOCK ERROR
                    ================================================== -->

                    <?php if ($stockError): ?>

                        <div class="checkout-stock-error">

                            <i class="bi bi-exclamation-triangle-fill"></i>

                            <div>

                                <strong>
                                    Insufficient Stock
                                </strong>

                                <span>
                                    Some items in your cart do not have enough stock.
                                    Please return to your cart and update your quantity.
                                </span>

                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- =================================================
                         PLACE ORDER
                    ================================================== -->

                    <button
                        type="submit"
                        class="place-order-button"
                        <?= $stockError ? "disabled" : "" ?>
                    >

                        <span>
                            <i class="bi bi-check-circle-fill"></i>
                            Place Order
                        </span>

                        <strong>
                            ₱<?= number_format($total, 2) ?>
                        </strong>

                    </button>


                    <!-- BACK TO CART -->

                    <a
                        href="cart.php"
                        class="back-cart-button"
                    >

                        <i class="bi bi-arrow-left"></i>

                        Back to Cart

                    </a>

                </form>

            </div>

        </section>

    </div>


    <!-- =================================================
         BACK HOME
    ================================================== -->

    <div class="back-home-container">

        <a
            href="../index.php"
            class="back-home-button"
        >

            <i class="bi bi-house-fill"></i>

            Back to Home

        </a>

    </div>

</main>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>

/* =====================================================
   ADDRESS ELEMENTS
===================================================== */

const savedAddressRadios =
    document.querySelectorAll(".address-radio");

const selectedAddressId =
    document.getElementById("selectedAddressId");

const newAddressSection =
    document.getElementById("newAddressSection");

const showNewAddress =
    document.getElementById("showNewAddress");

const cancelNewAddress =
    document.getElementById("cancelNewAddress");

const newAddressFields =
    document.querySelectorAll(".new-address-field");


/* =====================================================
   ENABLE NEW ADDRESS
===================================================== */

function enableNewAddress() {

    if (!newAddressSection) {
        return;
    }

    newAddressSection.classList.add("show");

    newAddressFields.forEach(function(field) {

        field.disabled = false;

        if (
            field.name !== "postal_code" &&
            field.type !== "checkbox"
        ) {
            field.required = true;
        }

    });


    savedAddressRadios.forEach(function(radio) {

        radio.checked = false;

    });


    document
        .querySelectorAll(".address-option")
        .forEach(function(option) {

            option.classList.remove("selected");

        });


    if (selectedAddressId) {

        selectedAddressId.value = "";

    }

}


/* =====================================================
   USE SAVED ADDRESS
===================================================== */

function useSavedAddress() {

    if (!newAddressSection) {
        return;
    }

    newAddressSection.classList.remove("show");

    newAddressFields.forEach(function(field) {

        field.disabled = true;
        field.required = false;

    });


    const checked =
        document.querySelector(
            ".address-radio:checked"
        );


    if (
        checked &&
        selectedAddressId
    ) {

        selectedAddressId.value =
            checked.dataset.addressId;

    }

}


/* =====================================================
   SAVED ADDRESS RADIO
===================================================== */

savedAddressRadios.forEach(function(radio) {

    radio.addEventListener(
        "change",
        function() {

            document
                .querySelectorAll(".address-option")
                .forEach(function(option) {

                    option.classList.remove("selected");

                });


            const parent =
                radio.closest(".address-option");


            if (parent) {

                parent.classList.add("selected");

            }


            useSavedAddress();

        }
    );

});


/* =====================================================
   ADD NEW ADDRESS
===================================================== */

if (showNewAddress) {

    showNewAddress.addEventListener(
        "click",
        enableNewAddress
    );

}


/* =====================================================
   CANCEL NEW ADDRESS
===================================================== */

if (cancelNewAddress) {

    cancelNewAddress.addEventListener(
        "click",
        function() {

            const firstSaved =
                document.querySelector(
                    ".address-radio"
                );


            if (firstSaved) {

                firstSaved.checked = true;

                firstSaved.dispatchEvent(
                    new Event("change")
                );

            }

        }
    );

}


/* =====================================================
   INITIAL ADDRESS STATE
===================================================== */

if (savedAddressRadios.length > 0) {

    const checked =
        document.querySelector(
            ".address-radio:checked"
        );


    if (checked) {

        const parent =
            checked.closest(".address-option");


        if (parent) {

            parent.classList.add("selected");

        }

    }

} else {

    newAddressFields.forEach(function(field) {

        field.disabled = false;

        if (
            field.name !== "postal_code" &&
            field.type !== "checkbox"
        ) {

            field.required = true;

        }

    });

}


/* =====================================================
   PAYMENT ELEMENTS
===================================================== */

const checkoutForm =
    document.getElementById("checkoutForm");

const paymentMethod =
    document.getElementById("paymentMethod");

const gcashPaymentSection =
    document.getElementById("gcashPaymentSection");

const gcashNumber =
    document.getElementById("gcashNumber");

const gcashReference =
    document.getElementById("gcashReference");


/* =====================================================
   TOGGLE GCASH
===================================================== */

function toggleGcashPayment() {

    if (
        !paymentMethod ||
        !gcashPaymentSection
    ) {
        return;
    }


    if (paymentMethod.value === "gcash") {

        gcashPaymentSection.classList.add("show");


        if (gcashNumber) {
            gcashNumber.required = true;
        }


        if (gcashReference) {
            gcashReference.required = true;
        }

    } else {

        gcashPaymentSection.classList.remove("show");


        if (gcashNumber) {

            gcashNumber.required = false;
            gcashNumber.value = "";

        }


        if (gcashReference) {

            gcashReference.required = false;
            gcashReference.value = "";

        }

    }

}


/* =====================================================
   PAYMENT CHANGE
===================================================== */

if (paymentMethod) {

    paymentMethod.addEventListener(
        "change",
        toggleGcashPayment
    );

}


/* =====================================================
   INITIAL GCASH STATE
===================================================== */

toggleGcashPayment();


/* =====================================================
   GCASH NUMBER - NUMBERS ONLY
===================================================== */

if (gcashNumber) {

    gcashNumber.addEventListener(
        "input",
        function() {

            this.value =
                this.value.replace(/\D/g, "");

            if (this.value.length > 11) {

                this.value =
                    this.value.substring(0, 11);

            }

        }
    );

}


/* =====================================================
   PHONE NUMBER - NUMBERS ONLY
===================================================== */

const phoneInputs =
    document.querySelectorAll(
        'input[name="phone"]'
    );

phoneInputs.forEach(function(input) {

    input.addEventListener(
        "input",
        function() {

            this.value =
                this.value.replace(/\D/g, "");

            if (this.value.length > 11) {

                this.value =
                    this.value.substring(0, 11);

            }

        }
    );

});


/* =====================================================
   CHECKOUT FORM VALIDATION
===================================================== */

if (checkoutForm) {

    checkoutForm.addEventListener(
        "submit",
        function(event) {


            /* ADDRESS */

            const savedSelected =
                document.querySelector(
                    ".address-radio:checked"
                );


            const hasSaved =
                savedAddressRadios.length > 0;


            const newAddressVisible =
                newAddressSection &&
                newAddressSection.classList.contains("show");


            if (
                hasSaved &&
                !savedSelected &&
                !newAddressVisible
            ) {

                event.preventDefault();

                alert(
                    "Please select a delivery address."
                );

                return;

            }


            /* GCASH */

            if (
                paymentMethod &&
                paymentMethod.value === "gcash"
            ) {


                const number =
                    gcashNumber
                        ? gcashNumber.value.trim()
                        : "";


                if (
                    !/^09\d{9}$/.test(number)
                ) {

                    event.preventDefault();

                    alert(
                        "Please enter a valid 11-digit GCash number starting with 09."
                    );

                    if (gcashNumber) {
                        gcashNumber.focus();
                    }

                    return;

                }


                const reference =
                    gcashReference
                        ? gcashReference.value.trim()
                        : "";


                if (reference === "") {

                    event.preventDefault();

                    alert(
                        "Please enter your GCash reference number."
                    );

                    if (gcashReference) {
                        gcashReference.focus();
                    }

                    return;

                }

            }

        }
    );

}

</script>


</body>
</html>