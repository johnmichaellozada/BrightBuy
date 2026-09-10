<?php

/**
 * BrightBuy Unified Login
 *
 * One login page for both customers and administrators.
 *
 * The user's role is checked after authentication:
 *
 * customer → BrightBuy homepage
 * admin    → Admin dashboard
 */

session_start();


/* =========================================================
   PREVENT ALREADY LOGGED-IN USERS FROM OPENING LOGIN PAGE
========================================================= */

if (
    isset($_SESSION["logged_in"]) &&
    $_SESSION["logged_in"] === true
) {

    /* =============================================
       CHECK USER ROLE
    ============================================= */

    if (
        isset($_SESSION["role"]) &&
        $_SESSION["role"] === "admin"
    ) {

        /* Admin → Admin Dashboard */

        header("Location: ../admin/admin-dashboard.php");
        exit;

    } elseif (
        isset($_SESSION["role"]) &&
        $_SESSION["role"] === "customer"
    ) {

        /* Customer → Homepage */

        header("Location: ../index.php");
        exit;

    } else {

        /* =============================================
           INVALID SESSION
        ============================================= */

        session_unset();
        session_destroy();

        /* Continue to login page */
    }
}


require_once "../db.php";


/* =========================================================
   VARIABLES
========================================================= */

$errors = [];
$email = "";


/* =========================================================
   PROCESS LOGIN
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";


    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($email === "") {

        $errors[] = "Email is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = "Please enter a valid email address.";
    }


    if ($password === "") {

        $errors[] = "Password is required.";
    }


    /* =====================================================
       AUTHENTICATE USER
    ===================================================== */

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            SELECT
                user_id,
                first_name,
                last_name,
                email,
                password,
                phone,
                role
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch();


        /* =================================================
           VERIFY EMAIL AND PASSWORD
        ================================================= */

        if (
            !$user ||
            !password_verify(
                $password,
                $user["password"]
            )
        ) {

            $errors[] = "Invalid email or password.";

        } else {

            /* =============================================
               REGENERATE SESSION ID
            ============================================= */

            session_regenerate_id(true);


            /* =============================================
               CREATE SESSION
            ============================================= */

            $_SESSION["user_id"] = (int)$user["user_id"];
            $_SESSION["first_name"] = $user["first_name"];
            $_SESSION["last_name"] = $user["last_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["phone"] = $user["phone"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["logged_in"] = true;


            /* =============================================
               ROLE-BASED REDIRECTION
            ============================================= */

            if ($user["role"] === "admin") {

                header(
                    "Location: ../admin/admin-dashboard.php"
                );
                exit;

            } elseif ($user["role"] === "customer") {

                header(
                    "Location: ../index.php"
                );
                exit;

            } else {

                /* Unknown/unsupported role */

                session_unset();
                session_destroy();

                $errors[] =
                    "Your account has an invalid user role.";
            }
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

    <title>BrightBuy | Login</title>


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


<body class="login-page">


<!-- =====================================================
     LOGIN CONTAINER
===================================================== -->

<div class="container login-container">

    <div class="row justify-content-center">

        <div class="col-md-6 col-lg-5">


            <!-- =================================================
                 LOGIN CARD
            ================================================== -->

            <div class="card login-card">

                <div class="card-body">


                    <!-- =================================================
                         LOGIN HEADER
                    ================================================== -->

                    <div class="login-heading">

                        <div class="login-icon">

                            <i class="bi bi-person-fill"></i>

                        </div>


                        <h2>
                            Welcome Back
                        </h2>


                        <p>
                            Sign in to your BrightBuy account
                        </p>

                    </div>


                    <!-- =================================================
                         ERROR MESSAGES
                    ================================================== -->

                    <?php if (!empty($errors)): ?>

                        <div
                            class="alert alert-danger login-alert"
                            role="alert"
                        >

                            <?php foreach ($errors as $error): ?>

                                <div>

                                    <i
                                        class="bi bi-exclamation-circle-fill me-1"
                                    ></i>

                                    <?= htmlspecialchars($error) ?>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>


                    <!-- =================================================
                         LOGIN FORM
                    ================================================== -->

                    <form
                        method="POST"
                        action="login.php"
                    >


                        <!-- EMAIL -->

                        <div class="mb-3">

                            <label
                                for="email"
                                class="form-label"
                            >

                                Email

                            </label>


                            <div class="login-input">

                                <i class="bi bi-envelope"></i>


                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="form-control"
                                    placeholder="Enter your email"
                                    value="<?= htmlspecialchars($email) ?>"
                                    autocomplete="email"
                                    required
                                >

                            </div>

                        </div>


                        <!-- PASSWORD -->

                        <div class="mb-4">

                            <label
                                for="password"
                                class="form-label"
                            >

                                Password

                            </label>


                            <div class="login-input">

                                <i class="bi bi-lock"></i>


                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="Enter your password"
                                    autocomplete="current-password"
                                    required
                                >

                            </div>

                        </div>


                        <!-- LOGIN BUTTON -->

                        <button
                            type="submit"
                            class="btn login-btn w-100"
                        >

                            <i class="bi bi-box-arrow-in-right"></i>

                            Login

                        </button>

                    </form>


                    <!-- =================================================
                         REGISTER
                    ================================================== -->

                    <div class="register-text">

                        Don't have an account?

                        <a href="register.php">

                            Register

                        </a>

                    </div>


                    <!-- =================================================
                         INFORMATION
                    ================================================== -->

                    <div class="text-center mt-4">

                        <small class="text-muted">

                            <i
                                class="bi bi-shield-check me-1"
                            ></i>

                            Customers and administrators use the same login.

                        </small>

                    </div>


                </div>

            </div>

        </div>

    </div>

</div>


</body>

</html>