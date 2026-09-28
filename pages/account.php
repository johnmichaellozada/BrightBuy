<?php

/**
 * BrightBuy Customer Account
 *
 * Displays the logged-in customer's account information
 * and provides account management actions.
 */


/* =========================================================
   SESSION & ACCESS CONTROL
========================================================= */

session_start();

require_once "../db.php";
require_once "../includes/auth.php";


/* =========================================================
   REQUIRE CUSTOMER LOGIN
========================================================= */

requireCustomer($pdo, "login.php");


/* =========================================================
   GET CURRENT CUSTOMER ID
========================================================= */

$userId = getCurrentUserId();

if ($userId === null) {

    header("Location: login.php");
    exit;
}


/* =========================================================
   GET CUSTOMER INFORMATION FROM DATABASE
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        user_id,
        first_name,
        last_name,
        email,
        phone,
        role
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$user = $stmt->fetch();


/* =========================================================
   CHECK IF CUSTOMER EXISTS
========================================================= */

if (!$user) {

    logoutUser();

    header("Location: login.php");
    exit;
}


/* =========================================================
   CUSTOMER INFORMATION
========================================================= */

$first_name = $user["first_name"] ?? "";
$last_name  = $user["last_name"] ?? "";
$email      = $user["email"] ?? "";
$phone      = $user["phone"] ?? "";
$role       = $user["role"] ?? "customer";


/* =========================================================
   UPDATE SESSION INFORMATION
========================================================= */

$_SESSION["user_id"]    = (int)$user["user_id"];
$_SESSION["first_name"] = $first_name;
$_SESSION["last_name"]  = $last_name;
$_SESSION["email"]      = $email;
$_SESSION["phone"]      = $phone;
$_SESSION["role"]       = $role;
$_SESSION["logged_in"]  = true;


/* =========================================================
   SESSION SUCCESS MESSAGE
========================================================= */

$success = $_SESSION["account_success"] ?? "";

unset($_SESSION["account_success"]);


/* =========================================================
   SESSION ERROR MESSAGE
========================================================= */

$errors = $_SESSION["account_errors"] ?? [];

unset($_SESSION["account_errors"]);


/* =========================================================
   ACCOUNT DELETED MESSAGE
========================================================= */

if (isset($_GET["deleted"]) && $_GET["deleted"] === "1") {

    $success = "Your account has been deleted successfully.";

}

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


        <!-- ACCOUNT ICON -->

        <div class="account-icon">

            <i class="bi bi-person"></i>

        </div>


        <!-- ACCOUNT TITLE -->

        <h2 class="mb-1">

            My Account

        </h2>


        <!-- WELCOME MESSAGE -->

        <p class="mb-0">

            Welcome,
            <?= htmlspecialchars(
                $first_name,
                ENT_QUOTES,
                "UTF-8"
            ) ?>!

        </p>

    </div>

</section>



<!-- =========================================================
     ACCOUNT CONTENT
========================================================= -->

<div class="container py-5">


    <!-- =====================================================
         SUCCESS MESSAGE
    ====================================================== -->

    <?php if ($success !== ""): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert"
        >

            <i class="bi bi-check-circle-fill me-2"></i>

            <?= htmlspecialchars(
                $success,
                ENT_QUOTES,
                "UTF-8"
            ) ?>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         ERROR MESSAGE
    ====================================================== -->

    <?php if (!empty($errors)): ?>

        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >

            <i class="bi bi-exclamation-circle-fill me-2"></i>


            <?php foreach ($errors as $error): ?>

                <div>

                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>

                </div>

            <?php endforeach; ?>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         ACCOUNT ROW
    ====================================================== -->

    <div class="row g-4">


        <!-- =================================================
             PERSONAL INFORMATION
        ================================================== -->

        <div class="col-lg-7">

            <div class="card account-card">

                <div class="card-body p-4">


                    <!-- TITLE -->

                    <h4 class="mb-4">

                        <i class="bi bi-person-circle"></i>

                        Personal Information

                    </h4>



                    <!-- =================================================
                         FULL NAME
                    ================================================== -->

                    <div class="account-info">

                        <div class="account-label">

                            Full Name

                        </div>

                        <div class="account-value">

                            <?= htmlspecialchars(
                                $first_name . " " . $last_name,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </div>

                    </div>



                    <!-- =================================================
                         EMAIL
                    ================================================== -->

                    <div class="account-info">

                        <div class="account-label">

                            Email Address

                        </div>

                        <div class="account-value">

                            <?= htmlspecialchars(
                                $email,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </div>

                    </div>



                    <!-- =================================================
                         PHONE NUMBER
                    ================================================== -->

                    <div class="account-info">

                        <div class="account-label">

                            Phone Number

                        </div>

                        <div class="account-value">

                            <?php if ($phone !== ""): ?>

                                <?= htmlspecialchars(
                                    $phone,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            <?php else: ?>

                                <span class="text-muted">

                                    No phone number added

                                </span>

                            <?php endif; ?>

                        </div>

                    </div>



                    <!-- =================================================
                         ACCOUNT TYPE
                    ================================================== -->

                    <div class="account-info">

                        <div class="account-label">

                            Account Type

                        </div>

                        <div class="account-value text-capitalize">

                            <?= htmlspecialchars(
                                $role,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </div>

                    </div>



                    <!-- =================================================
                         EDIT ACCOUNT
                    ================================================== -->

                    <div class="mt-4">

                        <a
                            href="edit-account.php"
                            class="btn btn-primary"
                        >

                            <i class="bi bi-pencil-square"></i>

                            Edit Account

                        </a>

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


                    <!-- MENU HEADING -->

                    <div class="p-4">

                        <h4 class="mb-0">

                            Account Menu

                        </h4>

                    </div>



                    <!-- =================================================
                         CONTINUE SHOPPING
                    ================================================== -->

                    <a href="shop.php">

                        <i class="bi bi-bag"></i>

                        <span>

                            Continue Shopping

                        </span>

                    </a>



                    <!-- =================================================
                         MY ORDERS
                    ================================================== -->

                    <a href="my-orders.php">

                        <i class="bi bi-bag-check"></i>

                        <span>

                            My Orders

                        </span>

                    </a>



                    <!-- =================================================
                         TRACK ORDER
                    ================================================== -->

                    <a href="track-order.php">

                        <i class="bi bi-box-seam"></i>

                        <span>

                            Track My Order

                        </span>

                    </a>



                    <!-- =================================================
                         HELP CENTER
                    ================================================== -->

                    <a href="help.php">

                        <i class="bi bi-question-circle"></i>

                        <span>

                            Help Center

                        </span>

                    </a>



                    <!-- =================================================
                         LOGOUT
                    ================================================== -->

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
         ACCOUNT ACTIONS
    ====================================================== -->

    <div class="text-center mt-4">


        <!-- EDIT ACCOUNT -->

        <a
            href="edit-account.php"
            class="btn btn-primary px-4 me-2"
        >

            <i class="bi bi-pencil-square"></i>

            Edit Account

        </a>



        <!-- DELETE ACCOUNT -->

        <form
            method="POST"
            action="delete-account.php"
            class="d-inline"
            onsubmit="return confirm(
                'Are you sure you want to permanently delete your account? This action cannot be undone.'
            );"
        >

            <button
                type="submit"
                class="btn btn-outline-danger px-4"
            >

                <i class="bi bi-trash"></i>

                Delete Account

            </button>

        </form>



        <!-- BACK TO HOME -->

        <a
            href="../index.php"
            class="btn btn-secondary px-4 ms-2"
        >

            <i class="bi bi-house"></i>

            Back to BrightBuy

        </a>

    </div>

</div>



<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>