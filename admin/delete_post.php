<?php
session_start();
require_once '../includes/functions.php';
require_once '../config.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get post ID from either GET or POST
$post_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $post_id = (int)$_GET['id'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_data = json_decode(file_get_contents('php://input'), true);
    if (isset($post_data['id'])) {
        $post_id = (int)$post_data['id'];
    } elseif (isset($_POST['id'])) {
        $post_id = (int)$_POST['id'];
    }
}

// Validate post ID
if (!$post_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Post ID not provided']);
    exit;
}

// Check if post exists and user has permission to delete it
$post = get_post($post_id);
if (!$post) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Post not found']);
    exit;
}

// Try to delete the post
if (delete_post($post_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to delete post']);
}

