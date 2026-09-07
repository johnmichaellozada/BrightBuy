<?php

session_start();
require_once "../db.php";

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}


/* =====================================================
   CUSTOMER ACCESS CHECK
   CHECK ROLE DIRECTLY FROM DATABASE
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

$currentUser = $userRoleStmt->fetch();

if (!$currentUser || $currentUser["role"] !== "customer") {
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

    $total +=
        (float)$item["price"] *
        (int)$item["quantity"];

    if (
        (int)$item["quantity"] >
        (int)$item["stock"]
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

$savedAddresses = $addressStmt->fetchAll();

$hasSavedAddress = !empty($savedAddresses);


/* =====================================================
   SELECT DEFAULT ADDRESS
===================================================== */

$defaultAddressId = null;

foreach ($savedAddresses as $address) {

    if ((int)$address["is_default"] === 1) {
        $defaultAddressId = (int)$address["address_id"];
        break;
    }
}


/* =====================================================
   IF NO DEFAULT, USE MOST RECENT SAVED ADDRESS
===================================================== */

if (
    $defaultAddressId === null &&
    $hasSavedAddress
) {
    $defaultAddressId =
        (int)$savedAddresses[0]["address_id"];
}


/* =====================================================
   PAGE
===================================================== */

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
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7ff;
            font-family: "Poppins", sans-serif;
        }

        .checkout-card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
        }

        .address-option {
            border: 2px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px;
            cursor: pointer;
            transition: 0.2s ease;
            background: #fff;
        }

        .address-option:hover {
            border-color: #0d47a1;
            background: #f8fbff;
        }

        .address-option.selected {
            border-color: #0d47a1;
            background: #f0f6ff;
        }

        .address-option input[type="radio"] {
            accent-color: #0d47a1;
        }

        .saved-address-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #e8f0ff;
            color: #0d47a1;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .address-details {
            line-height: 1.6;
        }

        .default-badge {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffe69c;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 20px;
        }

        .new-address-section {
            display: none;
        }

        .new-address-section.show {
            display: block;
        }

        .section-title {
            font-weight: 700;
        }

        .required-star {
            color: #dc3545;
        }

    </style>

</head>


<body>


<div class="container py-5">

    <!-- =================================================
         PAGE HEADER
    ================================================== -->

    <div class="text-center mb-5">

        <h1 class="fw-bold">
            <i class="bi bi-credit-card"></i>
            Checkout
        </h1>

        <p class="text-muted">
            Review your order and delivery information.
        </p>

    </div>


    <div class="row g-4">


        <!-- =================================================
             ORDER SUMMARY
        ================================================== -->

        <div class="col-lg-7">

            <div class="card checkout-card">

                <div class="card-body p-4">

                    <h4 class="section-title mb-4">
                        <i class="bi bi-bag-check"></i>
                        Your Order
                    </h4>


                    <?php foreach ($items as $item): ?>

                        <div
                            class="d-flex align-items-center border-bottom py-3"
                        >

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

                                    <?= htmlspecialchars(
                                        $item["product_name"]
                                    ) ?>

                                </h6>


                                <!-- STOCK -->

                                <?php if ((int)$item["stock"] > 10): ?>

                                    <small class="text-success fw-semibold d-block">

                                        <i class="bi bi-check-circle-fill"></i>

                                        <?= (int)$item["stock"] ?>
                                        available

                                    </small>

                                <?php elseif ((int)$item["stock"] > 0): ?>

                                    <small class="text-warning fw-semibold d-block">

                                        <i class="bi bi-exclamation-circle-fill"></i>

                                        Only
                                        <?= (int)$item["stock"] ?>
                                        left

                                    </small>

                                <?php else: ?>

                                    <small class="text-danger fw-semibold d-block">

                                        <i class="bi bi-x-circle-fill"></i>

                                        Out of Stock

                                    </small>

                                <?php endif; ?>


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
                                    $item["price"] *
                                    $item["quantity"],
                                    2
                                ) ?>

                            </strong>

                        </div>

                    <?php endforeach; ?>


                    <div
                        class="d-flex justify-content-between mt-4"
                    >

                        <span class="fw-bold">
                            Total
                        </span>

                        <span
                            class="fw-bold fs-4 text-primary"
                        >
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

            <div class="card checkout-card">

                <div class="card-body p-4">

                    <h4 class="section-title mb-4">
                        <i class="bi bi-geo-alt-fill"></i>
                        Delivery Information
                    </h4>


                    <form
                        method="POST"
                        action="place-order.php"
                        id="checkoutForm"
                    >


                        <!-- =================================================
                             SAVED ADDRESSES
                        ================================================== -->

                        <?php if ($hasSavedAddress): ?>

                            <div class="mb-4">

                                <label class="form-label fw-bold">
                                    Delivery Address
                                </label>


                                <?php foreach ($savedAddresses as $address): ?>

                                    <?php
                                    $addressId =
                                        (int)$address["address_id"];

                                    $isSelected =
                                        $addressId ===
                                        $defaultAddressId;
                                    ?>


                                    <label
                                        class="address-option d-block mb-3
                                        <?= $isSelected
                                            ? "selected"
                                            : "" ?>"
                                    >

                                        <div class="d-flex gap-3">

                                            <div class="pt-1">

                                                <input
                                                    type="radio"
                                                    name="address_option"
                                                    value="saved"
                                                    class="address-radio"
                                                    data-address-id="<?= $addressId ?>"
                                                    <?= $isSelected
                                                        ? "checked"
                                                        : "" ?>
                                                >

                                            </div>


                                            <div
                                                class="saved-address-icon"
                                            >

                                                <i
                                                    class="bi bi-house-door-fill"
                                                ></i>

                                            </div>


                                            <div class="address-details flex-grow-1">

                                                <div
                                                    class="d-flex
                                                    justify-content-between
                                                    align-items-center
                                                    gap-2 mb-1"
                                                >

                                                    <strong>

                                                        <?= htmlspecialchars(
                                                            $address[
                                                                "recipient_name"
                                                            ]
                                                        ) ?>

                                                    </strong>


                                                    <?php if (
                                                        (int)$address["is_default"] === 1
                                                    ): ?>

                                                        <span
                                                            class="default-badge"
                                                        >
                                                            DEFAULT
                                                        </span>

                                                    <?php endif; ?>

                                                </div>


                                                <div class="small text-muted">

                                                    <div>
                                                        <i
                                                            class="bi bi-telephone"
                                                        ></i>

                                                        <?= htmlspecialchars(
                                                            $address["phone"]
                                                        ) ?>
                                                    </div>


                                                    <div>
                                                        <i
                                                            class="bi bi-house"
                                                        ></i>

                                                        <?= htmlspecialchars(
                                                            $address["address_line"]
                                                        ) ?>
                                                    </div>


                                                    <div>

                                                        <?= htmlspecialchars(
                                                            $address["barangay"]
                                                        ) ?>,

                                                        <?= htmlspecialchars(
                                                            $address["city"]
                                                        ) ?>

                                                    </div>


                                                    <div>

                                                        <?= htmlspecialchars(
                                                            $address["province"]
                                                        ) ?>

                                                        <?php if (
                                                            !empty(
                                                                $address[
                                                                    "postal_code"
                                                                ]
                                                            )
                                                        ): ?>

                                                            ,

                                                            <?= htmlspecialchars(
                                                                $address[
                                                                    "postal_code"
                                                                ]
                                                            ) ?>

                                                        <?php endif; ?>

                                                    </div>

                                                </div>

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


                                <button
                                    type="button"
                                    class="btn btn-outline-primary w-100"
                                    id="showNewAddress"
                                >

                                    <i class="bi bi-plus-circle"></i>
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

                                <div
                                    class="border-top pt-4 mb-3"
                                >

                                    <h5 class="fw-bold">

                                        <i
                                            class="bi bi-plus-circle"
                                        ></i>

                                        New Delivery Address

                                    </h5>

                                    <p class="text-muted small mb-0">
                                        Enter a different address for this order.
                                    </p>

                                </div>


                                <!-- Recipient -->

                                <div class="mb-3">

                                    <label class="form-label">
                                        Recipient Name
                                        <span class="required-star">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="recipient_name"
                                        class="form-control new-address-field"
                                        value="<?= htmlspecialchars(
                                            $_SESSION["first_name"] .
                                            " " .
                                            $_SESSION["last_name"]
                                        ) ?>"
                                        disabled
                                    >

                                </div>


                                <!-- Phone -->

                                <div class="mb-3">

                                    <label class="form-label">
                                        Phone Number
                                        <span class="required-star">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="phone"
                                        class="form-control new-address-field"
                                        value="<?= htmlspecialchars(
                                            $_SESSION["phone"] ?? ""
                                        ) ?>"
                                        placeholder="09XXXXXXXXX"
                                        disabled
                                    >

                                </div>


                                <!-- Address -->

                                <div class="mb-3">

                                    <label class="form-label">
                                        House/Building No. & Street
                                        <span class="required-star">*</span>
                                    </label>

                                    <textarea
                                        name="address_line"
                                        class="form-control new-address-field"
                                        rows="2"
                                        placeholder="e.g. Purok 1, National Highway"
                                        disabled
                                    ></textarea>

                                </div>


                                <!-- Barangay -->

                                <div class="mb-3">

                                    <label class="form-label">
                                        Barangay
                                        <span class="required-star">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="barangay"
                                        class="form-control new-address-field"
                                        placeholder="Enter barangay"
                                        disabled
                                    >

                                </div>


                                <!-- City -->

                                <div class="mb-3">

                                    <label class="form-label">
                                        City / Municipality
                                        <span class="required-star">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="city"
                                        class="form-control new-address-field"
                                        placeholder="Enter city or municipality"
                                        disabled
                                    >

                                </div>


                                <!-- Province -->

                                <div class="mb-3">

                                    <label class="form-label">
                                        Province
                                        <span class="required-star">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="province"
                                        class="form-control new-address-field"
                                        placeholder="Enter province"
                                        disabled
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
                                        class="form-control new-address-field"
                                        placeholder="Optional"
                                        disabled
                                    >

                                </div>


                                <!-- Save Default -->

                                <div class="form-check mb-3">

                                    <input
                                        class="form-check-input new-address-field"
                                        type="checkbox"
                                        name="save_as_default"
                                        value="1"
                                        id="saveAsDefault"
                                        disabled
                                    >

                                    <label
                                        class="form-check-label"
                                        for="saveAsDefault"
                                    >
                                        Make this my default address
                                    </label>

                                </div>


                                <button
                                    type="button"
                                    class="btn btn-outline-secondary w-100 mb-3"
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

                            <div class="mb-3">

                                <div
                                    class="alert alert-info"
                                >

                                    <i class="bi bi-info-circle-fill"></i>

                                    You don't have a saved delivery address yet.
                                    Please enter your address below.

                                </div>

                            </div>


                            <!-- Recipient -->

                            <div class="mb-3">

                                <label class="form-label">
                                    Recipient Name
                                    <span class="required-star">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="recipient_name"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $_SESSION["first_name"] .
                                        " " .
                                        $_SESSION["last_name"]
                                    ) ?>"
                                    required
                                >

                            </div>


                            <!-- Phone -->

                            <div class="mb-3">

                                <label class="form-label">
                                    Phone Number
                                    <span class="required-star">*</span>
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
                                    House/Building No. & Street
                                    <span class="required-star">*</span>
                                </label>

                                <textarea
                                    name="address_line"
                                    class="form-control"
                                    rows="2"
                                    placeholder="e.g. Purok 1, National Highway"
                                    required
                                ></textarea>

                            </div>


                            <!-- Barangay -->

                            <div class="mb-3">

                                <label class="form-label">
                                    Barangay
                                    <span class="required-star">*</span>
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
                                    <span class="required-star">*</span>
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
                                    <span class="required-star">*</span>
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


                            <!-- Save Default -->

                            <div class="form-check mb-3">

                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="save_as_default"
                                    value="1"
                                    id="saveAsDefaultNoAddress"
                                >

                                <label
                                    class="form-check-label"
                                    for="saveAsDefaultNoAddress"
                                >
                                    Make this my default address
                                </label>

                            </div>

                        <?php endif; ?>


                        <!-- =================================================
                             PAYMENT METHOD
                        ================================================== -->

                        <div class="mb-3">

                            <label class="form-label fw-bold">
                                Payment Method
                                <span class="required-star">*</span>
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


                        <!-- =================================================
                             STOCK ERROR
                        ================================================== -->

                        <?php if ($stockError): ?>

                            <div class="alert alert-danger mt-3">

                                <i
                                    class="bi bi-exclamation-triangle-fill"
                                ></i>

                                Some items in your cart do not have enough
                                stock. Please go back to your cart and
                                update your quantity.

                            </div>

                        <?php endif; ?>


                        <!-- =================================================
                             PLACE ORDER
                        ================================================== -->

                        <button
                            type="submit"
                            class="btn btn-primary w-100 py-2"
                            <?= $stockError ? "disabled" : "" ?>
                        >

                            <i class="bi bi-check-circle"></i>
                            Place Order

                        </button>


                        <!-- =================================================
                             BACK TO CART
                        ================================================== -->

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

    </div>


    <!-- =================================================
         BACK HOME
    ================================================== -->

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


<script>

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


    if (selectedAddressId) {
        selectedAddressId.value = "";
    }


    document
        .querySelectorAll(".address-option")
        .forEach(function(option) {
            option.classList.remove("selected");
        });
}


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


    if (checked && selectedAddressId) {

        selectedAddressId.value =
            checked.dataset.addressId;

    }
}


savedAddressRadios.forEach(function(radio) {

    radio.addEventListener("change", function() {

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

    });

});


if (showNewAddress) {

    showNewAddress.addEventListener(
        "click",
        enableNewAddress
    );

}


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
   INITIAL STATE
===================================================== */

if (savedAddressRadios.length > 0) {

    const checked =
        document.querySelector(
            ".address-radio:checked"
        );

    if (checked) {

        checked
            .closest(".address-option")
            ?.classList.add("selected");

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
   FORM VALIDATION
===================================================== */

const checkoutForm =
    document.getElementById("checkoutForm");

if (checkoutForm) {

    checkoutForm.addEventListener(
        "submit",
        function(event) {

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

        }
    );

}

</script>


</body>
</html>