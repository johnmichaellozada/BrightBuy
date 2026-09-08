<?php

/**
 * BrightBuy Database Connection
 *
 * This file establishes a connection between the BrightBuy
 * PHP application and the MySQL database using PDO.
 */

// ---------------------------------------------------------
// Database Configuration
// ---------------------------------------------------------

$host = "localhost";
$db   = "brightbuy";
$user = "root";
$pass = "";


// ---------------------------------------------------------
// Create Database Connection
// ---------------------------------------------------------

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass
    );

    // Enable PDO exceptions for database errors.
    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    // Return database results as associative arrays by default.
    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );


// ---------------------------------------------------------
// Handle Database Connection Errors
// ---------------------------------------------------------

} catch (PDOException $e) {

    // Stop the application if the database connection fails.
    die("Database connection failed: " . $e->getMessage());
}