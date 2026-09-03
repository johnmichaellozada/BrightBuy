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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>BrightBuy | Register</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-6 col-lg-5">

            <div class="card shadow">

                <div class="card-body p-4">

                    <h2 class="text-center mb-4">
                        Create Your Account
                    </h2>

                    <?php if (!empty($errors)): ?>

                        <div class="alert alert-danger">

                            <?php foreach ($errors as $error): ?>

                                <div>
                                    <?= htmlspecialchars($error) ?>
                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                    <?php if ($success !== ""): ?>

                        <div class="alert alert-success">
                            <?= htmlspecialchars($success) ?>
                        </div>

                    <?php endif; ?>

                    <form method="POST" action="register.php">

                        <div class="mb-3">

                            <label class="form-label">
                                First Name
                            </label>

                            <input
                                type="text"
                                name="first_name"
                                class="form-control"
                                value="<?= htmlspecialchars($first_name) ?>"
                                required
                            >

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Last Name
                            </label>

                            <input
                                type="text"
                                name="last_name"
                                class="form-control"
                                value="<?= htmlspecialchars($last_name) ?>"
                                required
                            >

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                value="<?= htmlspecialchars($email) ?>"
                                required
                            >

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                minlength="8"
                                required
                            >

                            <small class="text-muted">
                                Minimum 8 characters
                            </small>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Confirm Password
                            </label>

                            <input
                                type="password"
                                name="confirm_password"
                                class="form-control"
                                minlength="8"
                                required
                            >

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Register
                        </button>

                    </form>

                    <p class="text-center mt-3 mb-0">

                        Already have an account?

                        <a href="login.php">
                            Login
                        </a>

                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>