<?php
include 'config.php';

try {
    $conn->query("SELECT 1");
    echo "Database connection successful!";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage();
}

