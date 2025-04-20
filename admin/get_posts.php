<?php
session_start();
require_once '../includes/functions.php';
require_once '../config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display errors but log them
ini_set('log_errors', 1);

// Check if user is logged in
if (!is_logged_in()) {
    header('Content-Type: application/json');
    die(json_encode([
        'error' => 'Unauthorized',
        'draw' => intval($_POST['draw']),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]));
}

try {
    // Get parameters from DataTables
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    // Get sorting parameters
    $order_column = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 1;
    $order_dir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    // Map DataTables column index to database column names
    $columns = [
        0 => 'title',
        1 => 'created_at',
        2 => 'categories',
        3 => 'author',
        4 => 'actions'
    ];
    
    // Validate and set sort column
    $sort = isset($columns[$order_column]) ? $columns[$order_column] : 'created_at';
    if ($sort === 'categories' || $sort === 'author' || $sort === 'actions') {
        $sort = 'created_at'; // Default sort for virtual columns
    }
    
    // Calculate page number from start and length
    $page = ($start / $length) + 1;
    
    // Get filtered posts
    $posts = get_posts_advanced(
        $page,
        $length,
        $sort,
        $order_dir,
        '', // category filter
        '', // author filter
        $search
    );
    
    // Get total counts
    $total_posts = get_total_posts_count();
    $filtered_posts = get_total_posts_count('', '', $search);
    
    // Format data for DataTables
    $data = [];
    foreach ($posts as $post) {
        // Get post categories
        $post_categories = get_post_categories($post['id']);
        $category_names = array_column($post_categories, 'name');
        
        // Get author info
        $author = get_user($post['user_id'] ?? 1); // Fallback to user ID 1 if not set
        
        // Format the actions column with proper HTML escaping
        $actions = '<div class="flex space-x-2">' .
                  '<a href="edit_post.php?id=' . htmlspecialchars($post['id']) . '" class="text-blue-600 hover:text-blue-900">Edit</a>' .
                  '<button onclick="deletePost(' . htmlspecialchars($post['id']) . ')" class="text-red-600 hover:text-red-900">Delete</button>' .
                  '</div>';
        
        $data[] = [
            'title' => htmlspecialchars($post['title']),
            'date' => date('F j, Y', strtotime($post['created_at'])),
            'categories' => htmlspecialchars(implode(', ', $category_names)),
            'author' => htmlspecialchars($author['username'] ?? 'Unknown'),
            'actions' => $actions
        ];
    }
    
    // Prepare response
    $response = [
        'draw' => $draw,
        'recordsTotal' => intval($total_posts),
        'recordsFiltered' => intval($filtered_posts),
        'data' => $data
    ];
    
    // Log the response for debugging
    error_log('DataTables Response: ' . json_encode($response));
    
    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Error in get_posts.php: ' . $e->getMessage());
    
    // Send error response in DataTables format
    header('Content-Type: application/json');
    echo json_encode([
        'draw' => $draw ?? 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $e->getMessage()
    ]);
}

