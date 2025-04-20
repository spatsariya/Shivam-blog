<?php
session_start();
include '../config.php';
include '../includes/functions.php';

// Get statistics
$total_posts = get_post_count();
$total_categories = count(get_categories());
$total_users = count(get_all_users());

// Get user role
$is_admin = is_admin();
$current_user = get_user($_SESSION['user_id']);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Check if this is an AJAX request
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    // Get filter parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
    $order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
    $category_filter = isset($_GET['category']) ? $_GET['category'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Get filtered posts
    $posts = get_posts_advanced($page, 10, $sort, $order, $category_filter, '', $search);
    $total_posts = get_total_posts_count($category_filter, '', $search);
    $total_pages = ceil($total_posts / 10);
    
    // Prepare posts data
    $posts_data = array();
    foreach ($posts as $post) {
        $post_categories = get_post_categories($post['id']);
        $author = get_user($post['user_id']);
        $category_names = array_column($post_categories, 'name');
        
        $posts_data[] = array(
            'id' => $post['id'],
            'title' => $post['title'],
            'slug' => $post['slug'],
            'date' => date('F j, Y', strtotime($post['created_at'])),
            'categories' => implode(', ', $category_names),
            'author' => $author['username']
        );
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode(array(
        'posts' => $posts_data,
        'pagination' => array(
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_posts' => $total_posts
        )
    ));
    exit;
}


// Get all categories for the filter dropdown
$all_categories = get_categories();

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Your Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
                <div class="flex items-center gap-4">
                    <span class="text-gray-600">
                        Welcome, <?php echo htmlspecialchars($current_user['username']); ?> (<?php echo ucfirst($current_user['role']); ?>)
                    </span>
                    <a href="logout.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Quick Actions -->
            <section class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a href="create_post.php" class="flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i> Create New Post
                    </a>
                    <a href="categories.php" class="flex items-center justify-center px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-colors">
                        <i class="fas fa-tags mr-2"></i> Manage Categories
                    </a>
                    <a href="nav-menu.php" class="flex items-center justify-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors">
                        <i class="fas fa-bars mr-2"></i> Manage Nav Menu
                    </a>
                    <a href="manage_sections.php" class="flex items-center justify-center px-4 py-2 bg-pink-600 text-white rounded-md hover:bg-pink-700 transition-colors">
                        <i class="fas fa-th-large mr-2"></i> Manage Homepage
                    </a>
                    <?php if ($is_admin): ?>
                    <a href="manage_users.php" class="flex items-center justify-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                        <i class="fas fa-users mr-2"></i> Manage Users
                    </a>
                    <?php endif; ?>
                </div>
            </section>

            <!-- All Posts -->
            <section class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">All Posts</h2>
                
                <!-- Filters -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div>
                        <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Sort by:</label>
                        <select id="sort" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="date">Date</option>
                            <option value="title">Title</option>
                            <option value="author">Author</option>
                        </select>
                    </div>
                    <div>
                        <label for="order" class="block text-sm font-medium text-gray-700 mb-1">Order:</label>
                        <select id="order" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="desc">Descending</option>
                            <option value="asc">Ascending</option>
                        </select>
                    </div>
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Filter by Category:</label>
                        <select id="category" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Categories</option>
                            <?php foreach (get_categories() as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search:</label>
                        <input type="text" id="search" placeholder="Search posts..." class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Posts Table -->
                <div class="overflow-x-auto">
                    <table id="posts-table" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTable will populate this -->
                        </tbody>
                    </table>
                </div>
                <div id="posts-count" class="mt-4 text-sm text-gray-600">
                    Showing 1 to 0 of 0 posts
                </div>
            </section>

            <!-- Site Statistics -->
            <section class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Site Statistics</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="p-4 bg-blue-50 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Total Posts</h3>
                        <p class="text-3xl font-bold text-blue-600"><?php echo $total_posts; ?></p>
                    </div>
                    <div class="p-4 bg-green-50 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Total Categories</h3>
                        <p class="text-3xl font-bold text-green-600"><?php echo $total_categories; ?></p>
                    </div>
                    <div class="p-4 bg-purple-50 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Total Users</h3>
                        <p class="text-3xl font-bold text-purple-600"><?php echo $total_users; ?></p>
                    </div>
                </div>
            </section>
        </div>
    </div>



<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">

<script>
$(document).ready(function() {
    var table = $('#posts-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'get_posts.php',
            type: 'POST',
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', error, thrown);
                // Log the response for debugging
                console.log('Server response:', xhr.responseText);
            }
        },
        columns: [
            { 
                data: 'title',
                name: 'title'
            },
            { 
                data: 'date',
                name: 'created_at'
            },
            { 
                data: 'categories',
                name: 'categories',
                orderable: false
            },
            { 
                data: 'author',
                name: 'author',
                orderable: false
            },
            { 
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[1, 'desc']], // Sort by date by default
        pageLength: 10,
        language: {
            processing: "Loading posts...",
            zeroRecords: "No posts found",
            info: "Showing _START_ to _END_ of _TOTAL_ posts",
            infoEmpty: "Showing 0 to 0 of 0 posts",
            infoFiltered: "(filtered from _MAX_ total posts)"
        },
        drawCallback: function(settings) {
            // Re-initialize delete buttons after table redraw
            initializeDeleteButtons();
        }
    });

    // Add event listeners for custom filters
    $('#sort, #order, #category, #search').on('change keyup', function() {
        table.ajax.reload();
    });

    // Initialize delete buttons
    function initializeDeleteButtons() {
        $('[onclick^="deletePost"]').off('click').on('click', function(e) {
            e.preventDefault();
            var postId = $(this).attr('onclick').match(/\d+/)[0];
            deletePost(postId);
        });
    }
});

function deletePost(postId) {
    if (!postId) {
        console.error('No post ID provided');
        return;
    }

    if (confirm('Are you sure you want to delete this post?')) {
        fetch('delete_post.php?id=' + postId, {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the DataTable to reflect the changes
                $('#posts-table').DataTable().ajax.reload();
            } else {
                alert('Error deleting post: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting post. Please try again.');
        });
    }
}
</script>





    

</body>
</html>

<?php include '../includes/footer.php'; ?>

