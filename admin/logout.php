<?php

/**
 * BrightBuy Admin Logout
 *
 * Destroys the current admin session
 * and redirects the user to the admin login page.
 */

session_start();

/* Clear all session variables */
$_SESSION = [];

/* Destroy the session */
session_destroy();

/* Redirect to admin login */
header("Location: admin-login.php");
exit;