<?php

/* =========================================================
   SESSION & DATABASE
========================================================= */

session_start();

require_once "../db.php";


/* =========================================================
   REGISTRATION VARIABLES
========================================================= */

$errors = [];
$success = "";

$first_name = "";
$last_name = "";
$email = "";


/* =========================================================
   REGISTRATION PROCESS
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST["first_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";


    /* -----------------------------------------------------
       INPUT VALIDATION
    ----------------------------------------------------- */

    if ($first_name === "") {

        $errors[] = "First name is required.";

    }


    if ($last_name === "") {

        $errors[] = "Last name is required.";

    }


    if ($email === "") {

        $errors[] = "Email is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = "Please enter a valid email address.";

    }


    if ($password === "") {

        $errors[] = "Password is required.";

    } elseif (strlen($password) < 8) {

        $errors[] = "Password must be at least 8 characters.";

    }


    if ($confirm_password === "") {

        $errors[] = "Please confirm your password.";

    } elseif ($password !== $confirm_password) {

        $errors[] = "Passwords do not match.";

    }


    /* =====================================================
       CHECK EMAIL & CREATE ACCOUNT
    ===================================================== */

    if (empty($errors)) {


        /* -------------------------------------------------
           CHECK IF EMAIL ALREADY EXISTS
        ------------------------------------------------- */

        $check = $pdo->prepare(
            "SELECT user_id
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $check->execute([$email]);


        if ($check->fetch()) {

            $errors[] =
                "An account with this email already exists.";

        } else {


            /* -------------------------------------------------
               HASH PASSWORD
            ------------------------------------------------- */

            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            /* -------------------------------------------------
               INSERT NEW CUSTOMER
            ------------------------------------------------- */

            $stmt = $pdo->prepare(
                "INSERT INTO users
                (first_name, last_name, email, password)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->execute([
                $first_name,
                $last_name,
                $email,
                $password_hash
            ]);


            /* -------------------------------------------------
               REGISTRATION SUCCESS
            ------------------------------------------------- */

            $success =
                "Registration successful! You can now log in.";


            /* Clear form fields */

            $first_name = "";
            $last_name = "";
            $email = "";
        }
    }
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

    <title>BrightBuy | Register</title>


    <!-- =================================================
         BOOTSTRAP
    ================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =================================================
         BOOTSTRAP ICONS
    ================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >


    <!-- =================================================
         BRIGHTBUY MAIN CSS
    ================================================== -->

    <link
        rel="stylesheet"
        href="../css/styles.css"
    >

</head>


<body class="register-page">


<!-- =====================================================
     REGISTRATION CONTAINER
===================================================== -->

<div class="register-container">


    <!-- =================================================
         REGISTRATION CARD
    ================================================== -->

    <div class="register-card">


        <!-- =================================================
             REGISTER ICON
        ================================================== -->

        <div class="register-icon">

            <i class="bi bi-bag-heart-fill"></i>

        </div>


        <!-- =================================================
             REGISTRATION HEADER
        ================================================== -->

        <h1 class="register-title">

            Create Your Account

        </h1>


        <p class="register-subtitle">

            Join BrightBuy and start shopping today!

        </p>


        <div class="register-accent"></div>


        <!-- =================================================
             ERROR MESSAGES
        ================================================== -->

        <?php if (!empty($errors)): ?>

            <div class="register-error">

                <?php foreach ($errors as $error): ?>

                    <div>

                        <?= htmlspecialchars($error) ?>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             SUCCESS MESSAGE
        ================================================== -->

        <?php if (!empty($success)): ?>

            <div class="register-success">

                <?= htmlspecialchars($success) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             REGISTRATION FORM
        ================================================== -->

        <form
            method="POST"
            action="register.php"
        >


            <!-- =================================================
                 FIRST NAME
            ================================================== -->

            <div class="register-form-group">

                <label for="first_name">

                    First Name

                </label>


                <div class="register-input-wrapper">

                    <div class="register-input-icon">

                        <i class="bi bi-person-fill"></i>

                    </div>


                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        placeholder="Enter your first name"
                        value="<?= htmlspecialchars($first_name ?? '') ?>"
                        required
                    >

                </div>

            </div>


            <!-- =================================================
                 LAST NAME
            ================================================== -->

            <div class="register-form-group">

                <label for="last_name">

                    Last Name

                </label>


                <div class="register-input-wrapper">

                    <div class="register-input-icon">

                        <i class="bi bi-person-fill"></i>

                    </div>


                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        placeholder="Enter your last name"
                        value="<?= htmlspecialchars($last_name ?? '') ?>"
                        required
                    >

                </div>

            </div>


            <!-- =================================================
                 EMAIL
            ================================================== -->

            <div class="register-form-group">

                <label for="email">

                    Email

                </label>


                <div class="register-input-wrapper">

                    <div class="register-input-icon">

                        <i class="bi bi-envelope-fill"></i>

                    </div>


                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email address"
                        value="<?= htmlspecialchars($email ?? '') ?>"
                        required
                    >

                </div>

            </div>


            <!-- =================================================
                 PASSWORD
            ================================================== -->

            <div class="register-form-group">

                <label for="password">

                    Password

                </label>


                <div class="register-input-wrapper">

                    <div class="register-input-icon">

                        <i class="bi bi-lock-fill"></i>

                    </div>


                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a password"
                        minlength="8"
                        required
                    >

                </div>


                <div class="register-help">

                    Minimum 8 characters

                </div>

            </div>


            <!-- =================================================
                 CONFIRM PASSWORD
            ================================================== -->

            <div class="register-form-group">

                <label for="confirm_password">

                    Confirm Password

                </label>


                <div class="register-input-wrapper">

                    <div class="register-input-icon">

                        <i class="bi bi-lock-fill"></i>

                    </div>


                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm your password"
                        minlength="8"
                        required
                    >

                </div>

            </div>


            <!-- =================================================
                 REGISTER BUTTON
            ================================================== -->

            <button
                type="submit"
                class="register-button"
            >

                <i class="bi bi-person-plus-fill"></i>

                Register

            </button>

        </form>


        <!-- =================================================
             LOGIN LINK
        ================================================== -->

        <div class="register-login">

            Already have an account?

            <a href="login.php">

                Login

            </a>

        </div>

    </div>

</div>


</body>

</html>