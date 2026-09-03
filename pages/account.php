<?php

session_start();

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

$first_name = $_SESSION["first_name"] ?? "";
$last_name = $_SESSION["last_name"] ?? "";
$email = $_SESSION["email"] ?? "";
$phone = $_SESSION["phone"] ?? "";
$role = $_SESSION["role"] ?? "customer";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BrightBuy | My Account</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7ff;
            font-family: Arial, sans-serif;
        }

        .account-header {
            background: #073b9d;
            color: white;
            padding: 35px 0;
        }

        .account-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #ffc107;
            color: #073b9d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            margin: 0 auto 15px;
        }

        .account-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .account-info {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }

        .account-info:last-child {
            border-bottom: none;
        }

        .account-label {
            color: #777;
            font-size: 14px;
        }

        .account-value {
            font-weight: 600;
            color: #172b4d;
        }

        .account-menu a {
            text-decoration: none;
            color: #172b4d;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .account-menu a:hover {
            background: #f5f7ff;
        }

        .account-menu i {
            font-size: 20px;
            color: #073b9d;
        }

        .logout-link {
            color: #dc3545 !important;
        }

        .logout-link i {
            color: #dc3545;
        }

    </style>

</head>

<body>

    <!-- Account Header -->
    <section class="account-header">

        <div class="container text-center">

            <div class="account-icon">
                <i class="bi bi-person"></i>
            </div>

            <h2 class="mb-1">
                My Account
            </h2>

            <p class="mb-0">
                Welcome, <?= htmlspecialchars($first_name) ?>!
            </p>

        </div>

    </section>


    <!-- Account Content -->
    <div class="container py-5">

        <div class="row g-4">

            <!-- Personal Information -->
            <div class="col-lg-7">

                <div class="card account-card">

                    <div class="card-body p-4">

                        <h4 class="mb-4">
                            <i class="bi bi-person-circle"></i>
                            Personal Information
                        </h4>


                        <div class="account-info">

                            <div class="account-label">
                                Full Name
                            </div>

                            <div class="account-value">
                                <?= htmlspecialchars($first_name . " " . $last_name) ?>
                            </div>

                        </div>


                        <div class="account-info">

                            <div class="account-label">
                                Email Address
                            </div>

                            <div class="account-value">
                                <?= htmlspecialchars($email) ?>
                            </div>

                        </div>


                        <div class="account-info">

                            <div class="account-label">
                                Phone Number
                            </div>

                            <div class="account-value">

                                <?php if ($phone !== ""): ?>

                                    <?= htmlspecialchars($phone) ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        No phone number added
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <div class="account-info">

                            <div class="account-label">
                                Account Type
                            </div>

                            <div class="account-value text-capitalize">
                                <?= htmlspecialchars($role) ?>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Account Menu -->
            <div class="col-lg-5">

                <div class="card account-card">

                    <div class="card-body p-0 account-menu">

                        <div class="p-4">

                            <h4 class="mb-0">
                                Account Menu
                            </h4>

                        </div>


                        <a href="shop.php">

                            <i class="bi bi-bag"></i>

                            <span>
                                Continue Shopping
                            </span>

                        </a>

                        <a href="my-orders.php">

                            <i class="bi bi-bag-check"></i>

                             <span>
                                 My Orders
                            </span>

                        </a>


                        <a href="track-order.php">

                            <i class="bi bi-box-seam"></i>

                            <span>
                                Track My Order
                            </span>

                        </a>


                        <a href="help.php">

                            <i class="bi bi-question-circle"></i>

                            <span>
                                Help Center
                            </span>

                        </a>


                        <a href="logout.php" class="logout-link">

                            <i class="bi bi-box-arrow-right"></i>

                            <span>
                                Logout
                            </span>

                        </a>

                    </div>

                </div>

            </div>

        </div>


        <!-- Back to Home -->
        <div class="text-center mt-4">

            <a href="../index.php" class="btn btn-primary px-4">

                <i class="bi bi-house"></i>

                Back to BrightBuy

            </a>

        </div>

    </div>

</body>

</html>