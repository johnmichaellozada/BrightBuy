<?php

/**
 * BrightBuy Authentication Functions
 *
 * Handles session management and
 * customer/admin access control.
 */


/* =====================================================
   START SESSION
===================================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =====================================================
   CHECK IF USER IS LOGGED IN
===================================================== */

function isLoggedIn(): bool
{
    return isset($_SESSION["logged_in"])
        && $_SESSION["logged_in"] === true;
}


/* =====================================================
   GET CURRENT USER ID
===================================================== */

function getCurrentUserId(): ?int
{
    if (!isset($_SESSION["user_id"])) {
        return null;
    }

    return (int) $_SESSION["user_id"];
}


/* =====================================================
   GET CURRENT USER ROLE
===================================================== */

function getCurrentUserRole(): ?string
{
    return $_SESSION["role"] ?? null;
}


/* =====================================================
   REQUIRE LOGIN
===================================================== */

function requireLogin(string $redirect = "../pages/login.php"): void
{
    if (!isLoggedIn()) {
        header("Location: " . $redirect);
        exit;
    }
}


/* =====================================================
   REQUIRE ADMIN
===================================================== */

function requireAdmin(
    PDO $pdo,
    string $redirect = "../pages/login.php"
): void {

    requireLogin($redirect);

    $userId = getCurrentUserId();

    if ($userId === null) {
        header("Location: " . $redirect);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT role
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$userId]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user["role"] !== "admin") {

        http_response_code(403);

        die("Access denied. Administrator privileges required.");
    }
}


/* =====================================================
   REQUIRE CUSTOMER
===================================================== */

function requireCustomer(
    PDO $pdo,
    string $redirect = "login.php"
): void {

    requireLogin($redirect);

    $userId = getCurrentUserId();

    if ($userId === null) {
        header("Location: " . $redirect);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT role
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$userId]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user["role"] !== "customer") {

        http_response_code(403);

        die("Access denied. Customer privileges required.");
    }
}


/* =====================================================
   LOGOUT
===================================================== */

function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}