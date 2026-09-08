<?php

session_start();

require_once "../db.php";

$errors = [];

$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "") {

        $errors[] = "Email is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = "Please enter a valid email address.";

    }

    if ($password === "") {

        $errors[] = "Password is required.";

    }

    if (empty($errors)) {

        $stmt = $pdo->prepare(
            "SELECT user_id, first_name, last_name, email, password, phone, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password"])) {

            // CUSTOMER LOGIN ONLY
            if ($user["role"] !== "customer") {

                $errors[] = "This login is for customers only. Please use Admin Login.";

            } else {

                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["first_name"] = $user["first_name"];
                $_SESSION["last_name"] = $user["last_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["phone"] = $user["phone"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["logged_in"] = true;

                header("Location: ../index.php");
                exit;
            }

        } else {

            $errors[] = "Invalid email or password.";

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

<div class="container login-container">

    <div class="row justify-content-center">

        <div class="col-md-6 col-lg-5">

            <div class="card login-card">

                <div class="card-body">

                    <!-- Login Header -->
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


                    <!-- Error Messages -->
                    <?php if (!empty($errors)): ?>

                        <div class="alert alert-danger login-alert">

                            <?php foreach ($errors as $error): ?>

                                <div>
                                    <?= htmlspecialchars($error) ?>
                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>


                    <!-- Login Form -->
                    <form
                        method="POST"
                        action="login.php"
                    >

                        <!-- Email -->
                        <div class="mb-3">

                            <label class="form-label">
                                Email
                            </label>

                            <div class="login-input">

                                <i class="bi bi-envelope"></i>

                                <input
                                    type="email"
                                    name="email"
                                    class="form-control"
                                    placeholder="Enter your email"
                                    value="<?= htmlspecialchars($email) ?>"
                                    required
                                >

                            </div>

                        </div>


                        <!-- Password -->
                        <div class="mb-4">

                            <label class="form-label">
                                Password
                            </label>

                            <div class="login-input">

                                <i class="bi bi-lock"></i>

                                <input
                                    type="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="Enter your password"
                                    required
                                >

                            </div>

                        </div>


                        <!-- Login Button -->
                        <button
                            type="submit"
                            class="btn login-btn w-100"
                        >

                            <i class="bi bi-box-arrow-in-right"></i>

                            Login

                        </button>

                    </form>


                    <!-- Register -->
                    <div class="register-text">

                        Don't have an account?

                        <a href="register.php">
                            Register
                        </a>

                    </div>


                    <!-- Admin Section -->
                    <div class="admin-section">

                        <div class="admin-divider">

                            <span>
                                OR
                            </span>

                        </div>

                        <p class="admin-text">
                            Are you an administrator?
                        </p>

                        <a
                            href="admin-login.php"
                            class="btn admin-login-btn w-100"
                        >

                            <i class="bi bi-shield-lock-fill"></i>

                            Admin Login

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>