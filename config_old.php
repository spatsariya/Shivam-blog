<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'u722501111_beingshivam_in');
define('DB_USER', 'u722501111_beingshivam_in');
define('DB_PASS', '$#!v@M2025');

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    // Set charset to ensure proper encoding
    $conn->set_charset("utf8mb4");
    
    // Test the connection with a simple query
    $test = $conn->query("SELECT 1");
    if (!$test) {
        throw new Exception('Database test query failed');
    }
    
} catch (Exception $e) {
    // Log the error
    error_log($e->getMessage());
    
    // Display error (only in development)
    echo "<div style='color:red; background-color:#fff; padding:20px; margin:20px; border:2px solid red;'>";
    echo "<h2>Database Connection Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
    die();
}
?>

