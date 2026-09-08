<?php

/* =========================================================
   SESSION & ACCESS CONTROL
========================================================= */

session_start();

/*
|--------------------------------------------------------------------------
| Check if the customer is logged in
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {

    header("Location: login.php");
    exit;
}


/* =========================================================
   GET CUSTOMER SESSION INFORMATION
========================================================= */

$first_name = $_SESSION["first_name"] ?? "";
$last_name  = $_SESSION["last_name"] ?? "";
$email      = $_SESSION["email"] ?? "";
$phone      = $_SESSION["phone"] ?? "";
$role       = $_SESSION["role"] ?? "customer";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <!-- =====================================================
         PAGE INFORMATION
    ====================================================== -->

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>BrightBuy | My Account</title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         BOOTSTRAP ICONS
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         BRIGHTBUY MAIN CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../css/styles.css"
    >

</head>


<body class="account-page">


<!-- =========================================================
     ACCOUNT HEADER
========================================================= -->

<section class="account-header">

    <div class="container text-center">

        <!-- Account Icon -->
        <div class="account-icon">

            <i class="bi bi-person"></i>

        </div>


        <!-- Account Title -->
        <h2 class="mb-1">

            My Account

        </h2>


        <!-- Welcome Message -->
        <p class="mb-0">

            Welcome,
            <?= htmlspecialchars($first_name) ?>!

        </p>

    </div>

</section>



<!-- =========================================================
     ACCOUNT CONTENT
========================================================= -->

<div class="container py-5">

    <div class="row g-4">


        <!-- =================================================
             PERSONAL INFORMATION
        ================================================== -->

        <div class="col-lg-7">

            <div class="card account-card">

                <div class="card-body p-4">

                    <h4 class="mb-4">

                        <i class="bi bi-person-circle"></i>

                        Personal Information

                    </h4>


                    <!-- Full Name -->
                    <div class="account-info">

                        <div class="account-label">

                            Full Name

                        </div>

                        <div class="account-value">

                            <?= htmlspecialchars(
                                $first_name . " " . $last_name
                            ) ?>

                        </div>

                    </div>


                    <!-- Email -->
                    <div class="account-info">

                        <div class="account-label">

                            Email Address

                        </div>

                        <div class="account-value">

                            <?= htmlspecialchars($email) ?>

                        </div>

                    </div>


                    <!-- Phone Number -->
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


                    <!-- Account Type -->
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



        <!-- =================================================
             ACCOUNT MENU
        ================================================== -->

        <div class="col-lg-5">

            <div class="card account-card">

                <div class="card-body p-0 account-menu">


                    <!-- Menu Heading -->
                    <div class="p-4">

                        <h4 class="mb-0">

                            Account Menu

                        </h4>

                    </div>


                    <!-- Continue Shopping -->
                    <a href="shop.php">

                        <i class="bi bi-bag"></i>

                        <span>

                            Continue Shopping

                        </span>

                    </a>


                    <!-- My Orders -->
                    <a href="my-orders.php">

                        <i class="bi bi-bag-check"></i>

                        <span>

                            My Orders

                        </span>

                    </a>


                    <!-- Track Order -->
                    <a href="track-order.php">

                        <i class="bi bi-box-seam"></i>

                        <span>

                            Track My Order

                        </span>

                    </a>


                    <!-- Help Center -->
                    <a href="help.php">

                        <i class="bi bi-question-circle"></i>

                        <span>

                            Help Center

                        </span>

                    </a>


                    <!-- Logout -->
                    <a
                        href="logout.php"
                        class="logout-link"
                    >

                        <i class="bi bi-box-arrow-right"></i>

                        <span>

                            Logout

                        </span>

                    </a>

                </div>

            </div>

        </div>

    </div>



    <!-- =====================================================
         BACK TO HOME
    ====================================================== -->

    <div class="text-center mt-4">

        <a
            href="../index.php"
            class="btn btn-primary px-4"
        >

            <i class="bi bi-house"></i>

            Back to BrightBuy

        </a>

    </div>

</div>


</body>

</html>