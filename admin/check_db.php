<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../config.php';

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check posts table structure
$result = $conn->query("DESCRIBE posts");
if (!$result) {
    die("Error describing posts table: " . $conn->error);
}

echo "<h2>Posts Table Structure:</h2>";
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

// Check if any posts exist
$result = $conn->query("SELECT * FROM posts LIMIT 1");
if (!$result) {
    die("Error querying posts: " . $conn->error);
}

echo "<h2>Sample Post (if exists):</h2>";
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

// Check user session
echo "<h2>Session Info:</h2>";
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

