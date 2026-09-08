<?php

session_start();

require_once "../db.php";

$errors = [];
$success = "";

$first_name = "";
$last_name = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST["first_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

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

    if (empty($errors)) {

        $check = $pdo->prepare(
            "SELECT user_id FROM users WHERE email = ? LIMIT 1"
        );

        $check->execute([$email]);

        if ($check->fetch()) {

            $errors[] = "An account with this email already exists.";

        } else {

            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

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

            $success = "Registration successful! You can now log in.";

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

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BrightBuy | Register</title>

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

    <!-- BrightBuy Main CSS -->
    <link
        rel="stylesheet"
        href="../css/styles.css"
    >

</head>

<body class="register-page">

    <div class="register-container">

        <div class="register-card">

            <!-- Register Icon -->
            <div class="register-icon">
                <i class="bi bi-bag-heart-fill"></i>
            </div>

            <!-- Heading -->
            <h1 class="register-title">
                Create Your Account
            </h1>

            <p class="register-subtitle">
                Join BrightBuy and start shopping today!
            </p>

            <div class="register-accent"></div>


            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>

                <div class="register-error">

                    <?php foreach ($errors as $error): ?>

                        <div>
                            <?= htmlspecialchars($error) ?>
                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>


            <!-- Registration Form -->
            <form method="POST" action="register.php">

                <!-- First Name -->
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


                <!-- Last Name -->
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


                <!-- Email -->
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


                <!-- Password -->
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


                <!-- Confirm Password -->
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


                <!-- Register Button -->
                <button
                    type="submit"
                    class="register-button"
                >

                    <i class="bi bi-person-plus-fill"></i>

                    Register

                </button>

            </form>


            <!-- Login -->
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