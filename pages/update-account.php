<?php

/**
 * BrightBuy Customer Update Account
 *
 * Processes changes made to the customer's
 * personal information.
 */

session_start();

require_once "../db.php";
require_once "../includes/auth.php";

requireCustomer($pdo, "login.php");


/* =========================================================
   ONLY ALLOW POST
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: account.php");
    exit;
}


/* =========================================================
   GET CUSTOMER ID
========================================================= */

$userId = getCurrentUserId();

if ($userId === null) {

    header("Location: login.php");
    exit;
}


/* =========================================================
   GET FORM DATA
========================================================= */

$first_name = trim($_POST["first_name"] ?? "");
$last_name  = trim($_POST["last_name"] ?? "");
$email      = trim($_POST["email"] ?? "");
$phone      = trim($_POST["phone"] ?? "");


/* =========================================================
   VALIDATION
========================================================= */

$errors = [];


/* FIRST NAME */

if ($first_name === "") {

    $errors[] = "First name is required.";

} elseif (strlen($first_name) > 100) {

    $errors[] = "First name is too long.";

}


/* LAST NAME */

if ($last_name === "") {

    $errors[] = "Last name is required.";

} elseif (strlen($last_name) > 100) {

    $errors[] = "Last name is too long.";

}


/* EMAIL */

if ($email === "") {

    $errors[] = "Email is required.";

} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $errors[] = "Please enter a valid email address.";

}


/* =========================================================
   CHECK EMAIL
========================================================= */

if (empty($errors)) {

    $check = $pdo->prepare("
        SELECT user_id
        FROM users
        WHERE email = ?
        AND user_id != ?
        LIMIT 1
    ");

    $check->execute([
        $email,
        $userId
    ]);

    if ($check->fetch()) {

        $errors[] =
            "Another account is already using this email.";

    }
}


/* =========================================================
   STOP IF ERRORS
========================================================= */

if (!empty($errors)) {

    $_SESSION["account_errors"] = $errors;

    header("Location: edit-account.php");
    exit;
}


/* =========================================================
   UPDATE CUSTOMER
========================================================= */

try {

    $stmt = $pdo->prepare("
        UPDATE users
        SET
            first_name = ?,
            last_name = ?,
            email = ?,
            phone = ?
        WHERE user_id = ?
    ");

    $stmt->execute([
        $first_name,
        $last_name,
        $email,
        $phone,
        $userId
    ]);


    /* =====================================================
       UPDATE SESSION
    ===================================================== */

    $_SESSION["first_name"] = $first_name;
    $_SESSION["last_name"]  = $last_name;
    $_SESSION["email"]      = $email;
    $_SESSION["phone"]      = $phone;


    /* =====================================================
       SUCCESS MESSAGE
    ===================================================== */

    $_SESSION["account_success"] =
        "Account information updated successfully.";


    header("Location: account.php");
    exit;


} catch (PDOException $e) {

    $_SESSION["account_errors"] = [
        "Unable to update your account. Please try again."
    ];

    header("Location: edit-account.php");
    exit;
}