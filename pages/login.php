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
            "SELECT user_id, first_name, last_name, email, password
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password"])) {

            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["first_name"] = $user["first_name"];
            $_SESSION["last_name"] = $user["last_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["logged_in"] = true;

            header("Location: ../index.php");
            exit;

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
                        Welcome Back
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

                    <form method="POST" action="login.php">

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
                                required
                            >

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Login
                        </button>

                    </form>

                    <p class="text-center mt-3 mb-0">

                        Don't have an account?

                        <a href="register.php">
                            Register
                        </a>

                        <div class="text-center mt-4">

    <div class="border-top pt-3">

        <p class="text-muted mb-2">
            Are you an administrator?
        </p>

        <a
            href="admin-login.php"
            class="btn btn-outline-dark w-100"
        >
            <i class="bi bi-shield-lock-fill"></i>
            Admin Login
        </a>

    </div>

</div>

                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>