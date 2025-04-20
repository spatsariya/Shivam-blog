<?php
session_start();
include '../config.php';
include '../includes/functions.php';

if (!is_logged_in()) {
    die('Unauthorized');
}

$accepted_origins = array("http://localhost", "https://yourdomain.com");

if (isset($_SERVER['HTTP_ORIGIN'])) {
    if (in_array($_SERVER['HTTP_ORIGIN'], $accepted_origins)) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    } else {
        header("HTTP/1.1 403 Origin Denied");
        return;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    return;
}

reset($_FILES);
$temp = current($_FILES);

if (is_uploaded_file($temp['tmp_name'])) {
    if (preg_match("/([^\w\s\d\-_~,;:\[\]$$$$.])|([\.]{2,})/", $temp['name'])) {
        header("HTTP/1.1 400 Invalid file name.");
        return;
    }

    $file_name = preg_replace('/\s+/', '-', $temp['name']);
    $file_name = preg_replace('/[^a-zA-Z0-9\-\.]/', '', $file_name);
    $file_name = strtolower($file_name);

    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $extensions = array("jpeg", "jpg", "png", "gif");

    if (!in_array($file_ext, $extensions)) {
        header("HTTP/1.1 400 Invalid extension.");
        return;
    }

    $upload_dir = '../uploads/';
    $file_name = uniqid() . '-' . $file_name;
    $file_path = $upload_dir . $file_name;

    if (move_uploaded_file($temp['tmp_name'], $file_path)) {
        $file_url = '/uploads/' . $file_name;
        echo json_encode(array('location' => $file_url));
    } else {
        header("HTTP/1.1 500 Server Error");
    }
} else {
    header("HTTP/1.1 500 Server Error");
}

