<?php
// Set PHP timezone to India Standard Time
date_default_timezone_set('Asia/Kolkata');

// Enable error reporting for debugging (comment out in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection parameters - HOSTINGER VERSION
$host = "localhost";  // For Hostinger, this should be "localhost"
$user = "u568372288_nnrgcafe";     // Your database username
$password = "227Z1A1209a";          // Your database password  
$dbname = "u568372288_nnrgcafe";   // Your database name

// Alternative hosts to try if localhost doesn't work (uncomment if needed)
// $host = "127.0.0.1";  // Sometimes Hostinger uses this instead
// $host = "mysql.hostinger.com";  // Or this (check your Hostinger panel)

// Create connection using MySQLi with error handling
$mysqli = new mysqli($host, $user, $password, $dbname);

// Check connection with detailed debugging
if ($mysqli->connect_error) {
    // Log detailed error information
    $error_msg = "Database Connection Failed!\n";
    $error_msg .= "Error: " . $mysqli->connect_error . "\n";
    $error_msg .= "Host: $host\n";
    $error_msg .= "User: $user\n";
    $error_msg .= "Database: $dbname\n";
    $error_msg .= "Time: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Log to file
    file_put_contents('db_connection_error.log', $error_msg, FILE_APPEND | LOCK_EX);
    
    // Display error for debugging (remove in production)
    die("Database Connection Failed: " . $mysqli->connect_error . 
        "<br>Please check db_connection_error.log for details");
}

// Set MySQL timezone to IST (+05:30) - Important for Hostinger
$timezone_result = $mysqli->query("SET time_zone = '+05:30'");
if (!$timezone_result) {
    error_log("Warning: Could not set timezone: " . $mysqli->error);
    // Try alternative timezone setting
    $mysqli->query("SET time_zone = 'Asia/Kolkata'");
}

// Set character set for better compatibility
if (!$mysqli->set_charset("utf8mb4")) {
    // Fallback to utf8 if utf8mb4 not supported
    if (!$mysqli->set_charset("utf8")) {
        error_log("Warning: Could not set character set: " . $mysqli->error);
    }
}

// Set SQL mode for better compatibility
$mysqli->query("SET sql_mode = ''");

// Create alias for backward compatibility
$conn = $mysqli;

// Log successful connection (comment out in production)
file_put_contents('db_connection_success.log', 
    "✅ Database connected successfully at " . date('Y-m-d H:i:s') . "\n", 
    FILE_APPEND | LOCK_EX);

// Test connection with a simple query
try {
    $test_query = $mysqli->query("SELECT 1 as test");
    if (!$test_query) {
        throw new Exception("Test query failed: " . $mysqli->error);
    }
} catch (Exception $e) {
    error_log("Database test query failed: " . $e->getMessage());
    die("Database test failed: " . $e->getMessage());
}

?>