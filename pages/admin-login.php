<?php

// =====================================================
// SESSION AND DATABASE
// =====================================================

session_start();
require_once "../db.php";


// =====================================================
// INITIALIZE ERROR MESSAGE
// =====================================================

$error = "";


// =====================================================
// ADMIN LOGIN PROCESS
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get submitted login information
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";


    // =================================================
    // VALIDATION
    // =================================================

    if ($email === "" || $password === "") {

        $error = "Please enter your email and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        // =================================================
        // FIND ADMIN ACCOUNT
        // =================================================

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


        // =================================================
        // VERIFY ADMIN ACCOUNT
        // =================================================

        if (
            $user &&
            $user["role"] === "admin" &&
            password_verify($password, $user["password"])
        ) {

            // Regenerate session ID for security
            session_regenerate_id(true);


            // =================================================
            // STORE ADMIN INFORMATION IN SESSION
            // =================================================

            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["first_name"] = $user["first_name"];
            $_SESSION["last_name"] = $user["last_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["phone"] = $user["phone"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["logged_in"] = true;


            // Redirect to admin dashboard
            header("Location: admin-dashboard.php");
            exit;

        } else {

            $error = "Invalid administrator email or password.";

        }
    }
}

?>