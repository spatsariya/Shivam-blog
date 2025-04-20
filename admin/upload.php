<?php
session_start();
include '../config.php';
include '../includes/functions.php';

if (!is_logged_in()) {
    die(json_encode(['error' => 'Not authorized']));
}

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $temp_name = $_FILES['file']['tmp_name'];
    $name = basename($_FILES['file']['name']);
    
    // Generate unique filename
    $filename = uniqid() . '-' . $name;
    $filepath = $upload_dir . '/' . $filename;
    
    // Check if it's a valid image
    $check = getimagesize($temp_name);
    if ($check !== false) {
        if (move_uploaded_file($temp_name, $filepath)) {
            // Return the URL for the uploaded file
            $file_url = '/uploads/' . $filename;
            echo json_encode(['location' => $file_url]);
            exit;
        }
    }
}

// If we get here, something went wrong
echo json_encode(['error' => 'Failed to upload file']);

