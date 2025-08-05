<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gpagenie";

$conn = null; // Initialize $conn

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        // For a real application, you might log this error instead of dying,
        // especially if this file is included by an AJAX handler that should return JSON.
        // However, if the DB connection itself fails, it's a critical issue.
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    // If db_connect.php is included by a script that should return JSON on error:
    if (!headers_sent()) { // Check if headers are already sent
        header('Content-Type: application/json');
        http_response_code(500); // Internal Server Error
        echo json_encode(['status' => 'error', 'message' => 'Database connection error: ' . $e->getMessage()]);
    } else {
        // Fallback if headers are already sent (e.g., HTML output started)
        die("Database connection error: " . $e->getMessage());
    }
    exit(); // Stop script execution
}
?>