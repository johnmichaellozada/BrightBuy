<?php

session_start();

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}

$order_id = filter_input(
    INPUT_GET,
    "order_id",
    FILTER_VALIDATE_INT
);

if (!$order_id) {
    header("Location: ../index.php");
    exit;
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

    <title>Order Successful - BrightBuy</title>

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

    <div class="text-center">

        <i
            class="bi bi-check-circle-fill text-success"
            style="font-size: 80px;"
        ></i>

        <h1 class="fw-bold mt-4">
            Order Successful!
        </h1>

        <p class="text-muted">
            Thank you for shopping with BrightBuy.
        </p>

        <p>
            Your Order ID is:
            <strong>
                #<?= (int)$order_id ?>
            </strong>
        </p>


        <div class="mt-4">

            <a
                href="../index.php"
                class="btn btn-primary px-4 me-2"
            >
                <i class="bi bi-house"></i>
                Back to Home
            </a>

            <a
                href="account.php"
                class="btn btn-outline-primary px-4"
            >
                <i class="bi bi-person"></i>
                My Account
            </a>

        </div>

    </div>

</div>

</body>

</html>