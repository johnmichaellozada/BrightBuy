<?php

session_start();

require_once "../db.php";

$error = "";


/* =====================================================
   ADMIN LOGIN
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";


    /* =================================================
       VALIDATION
    ================================================= */

    if ($email === "" || $password === "") {

        $error = "Please enter your email and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        /* =============================================
           GET USER
        ============================================= */

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


        /* =============================================
           VERIFY ADMIN ACCOUNT
        ============================================= */

        if (
            $user &&
            $user["role"] === "admin" &&
            password_verify($password, $user["password"])
        ) {

            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["first_name"] = $user["first_name"];
            $_SESSION["last_name"] = $user["last_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["phone"] = $user["phone"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["logged_in"] = true;

            header("Location: admin-dashboard.php");
            exit;

        } else {

            $error = "Invalid administrator email or password.";

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

    <title>BrightBuy | Admin Login</title>

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

    <!-- Google Font -->
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Poppins", sans-serif;
            background: #f5f7ff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .admin-login-wrapper {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .admin-login-card {
            background: white;
            border-radius: 18px;
            padding: 40px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.10);
        }

        .admin-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #073b9d;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
        }

        .admin-title {
            color: #073b9d;
            font-weight: 800;
            text-align: center;
            margin-bottom: 5px;
        }

        .admin-subtitle {
            color: #777;
            text-align: center;
            margin-bottom: 30px;
        }

        .form-label {
            font-weight: 600;
        }

        .form-control {
            min-height: 48px;
            border-radius: 8px;
        }

        .login-button {
            width: 100%;
            min-height: 48px;
            border: none;
            border-radius: 8px;
            background: #073b9d;
            color: white;
            font-weight: 700;
            transition: 0.2s;
        }

        .login-button:hover {
            background: #052d7a;
        }

        .customer-login {
            text-align: center;
            margin-top: 25px;
        }

        .customer-login a {
            color: #073b9d;
            font-weight: 600;
            text-decoration: none;
        }

        .customer-login a:hover {
            text-decoration: underline;
        }

        .admin-label {
            display: inline-block;
            background: #e8efff;
            color: #073b9d;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 15px;
        }

    </style>

</head>

<body>

<div class="admin-login-wrapper">

    <div class="admin-login-card">

        <div class="text-center">

            <div class="admin-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>

            <span class="admin-label">
                ADMINISTRATOR
            </span>

            <h2 class="admin-title">
                Admin Login
            </h2>

            <p class="admin-subtitle">
                Sign in to manage BrightBuy.
            </p>

        </div>


        <?php if ($error !== ""): ?>

            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <form method="POST" action="">

            <!-- EMAIL -->

            <div class="mb-3">

                <label class="form-label">
                    Admin Email
                </label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="Enter admin email"
                    value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                    required
                >

            </div>


            <!-- PASSWORD -->

            <div class="mb-4">

                <label class="form-label">
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="Enter admin password"
                    required
                >

            </div>


            <!-- LOGIN -->

            <button
                type="submit"
                class="login-button"
            >
                <i class="bi bi-box-arrow-in-right"></i>
                Login as Administrator
            </button>

        </form>


        <!-- CUSTOMER LOGIN -->

        <div class="customer-login">

            <span class="text-muted">
                Are you a customer?
            </span>

            <br>

            <a href="login.php">
                <i class="bi bi-person"></i>
                Customer Login
            </a>

        </div>

    </div>

</div>

</body>

</html>