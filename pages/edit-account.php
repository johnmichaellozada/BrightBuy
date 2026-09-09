<?php

/**
 * BrightBuy Customer Edit Account
 *
 * Allows the logged-in customer to edit
 * their personal information.
 */

session_start();

require_once "../db.php";
require_once "../includes/auth.php";

requireCustomer($pdo, "login.php");


/* =========================================================
   GET CURRENT CUSTOMER
========================================================= */

$userId = getCurrentUserId();

$stmt = $pdo->prepare("
    SELECT
        user_id,
        first_name,
        last_name,
        email,
        phone
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$user = $stmt->fetch();


if (!$user) {

    session_destroy();

    header("Location: login.php");
    exit;
}


/* =========================================================
   DISPLAY VARIABLES
========================================================= */

$first_name = $user["first_name"] ?? "";
$last_name  = $user["last_name"] ?? "";
$email      = $user["email"] ?? "";
$phone      = $user["phone"] ?? "";


/* =========================================================
   GET SESSION MESSAGE
========================================================= */

$errors = $_SESSION["account_errors"] ?? [];

unset($_SESSION["account_errors"]);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>BrightBuy | Edit Account</title>

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


<body class="account-page">


<!-- =========================================================
     HEADER
========================================================= -->

<section class="account-header">

    <div class="container text-center">

        <div class="account-icon">

            <i class="bi bi-person-gear"></i>

        </div>

        <h2 class="mb-1">

            Edit Account

        </h2>

        <p class="mb-0">

            Update your BrightBuy account information.

        </p>

    </div>

</section>


<!-- =========================================================
     FORM
========================================================= -->

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-7">

            <div class="card account-card">

                <div class="card-body p-4">

                    <h4 class="mb-4">

                        <i class="bi bi-person-circle"></i>

                        Personal Information

                    </h4>


                    <!-- ERRORS -->

                    <?php if (!empty($errors)): ?>

                        <div class="alert alert-danger">

                            <?php foreach ($errors as $error): ?>

                                <div>

                                    <?= htmlspecialchars($error) ?>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>


                    <!-- FORM -->

                    <form
                        method="POST"
                        action="update-account.php"
                    >


                        <!-- FIRST NAME -->

                        <div class="mb-3">

                            <label
                                for="first_name"
                                class="form-label"
                            >

                                First Name

                            </label>

                            <input
                                type="text"
                                id="first_name"
                                name="first_name"
                                class="form-control"
                                value="<?= htmlspecialchars($first_name) ?>"
                                required
                            >

                        </div>


                        <!-- LAST NAME -->

                        <div class="mb-3">

                            <label
                                for="last_name"
                                class="form-label"
                            >

                                Last Name

                            </label>

                            <input
                                type="text"
                                id="last_name"
                                name="last_name"
                                class="form-control"
                                value="<?= htmlspecialchars($last_name) ?>"
                                required
                            >

                        </div>


                        <!-- EMAIL -->

                        <div class="mb-3">

                            <label
                                for="email"
                                class="form-label"
                            >

                                Email Address

                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                value="<?= htmlspecialchars($email) ?>"
                                required
                            >

                        </div>


                        <!-- PHONE -->

                        <div class="mb-4">

                            <label
                                for="phone"
                                class="form-label"
                            >

                                Phone Number

                            </label>

                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                class="form-control"
                                value="<?= htmlspecialchars($phone) ?>"
                                placeholder="Enter your phone number"
                            >

                        </div>


                        <!-- BUTTONS -->

                        <div class="d-flex gap-2">

                            <a
                                href="account.php"
                                class="btn btn-secondary"
                            >

                                <i class="bi bi-arrow-left"></i>

                                Cancel

                            </a>


                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i class="bi bi-save"></i>

                                Save Changes

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>


</body>

</html>