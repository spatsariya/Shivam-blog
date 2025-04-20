<?php
session_start();
include '../config.php';
include '../includes/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'create' && !empty($_POST['category_name'])) {
            if (create_category($_POST['category_name'])) {
                $message = "Category created successfully!";
                $message_type = "success";
            } else {
                $message = "Failed to create category. It might already exist.";
                $message_type = "error";
            }
        } elseif ($_POST['action'] == 'delete' && !empty($_POST['category_id'])) {
            if (delete_category($_POST['category_id'])) {
                $message = "Category deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Failed to delete category.";
                $message_type = "error";
            }
        }
    }
}

$categories = get_categories();

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900">Manage Categories</h1>
                <a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-200">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if ($message): ?>
            <div class="mb-4 rounded-lg p-4 <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Create Category Form -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Create New Category</h2>
                <form action="categories.php" method="post" class="flex items-center space-x-4">
                    <input type="hidden" name="action" value="create">
                    <div class="flex-grow">
                        <input 
                            type="text" 
                            name="category_name" 
                            placeholder="Enter category name" 
                            required
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        >
                    </div>
                    <button 
                        type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <i class="fas fa-plus mr-2"></i> Create Category
                    </button>
                </form>
            </div>
        </div>

        <!-- Categories List -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Existing Categories</h2>
                <?php if (empty($categories)): ?>
                    <p class="text-gray-500 text-center py-4">No categories found. Create your first category above.</p>
                <?php else: ?>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($categories as $category): ?>
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($category['name']); ?></span>
                                <form action="categories.php" method="post" class="inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
    // Add any JavaScript enhancements here
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide messages after 5 seconds
        const messages = document.querySelectorAll('[role="alert"]');
        messages.forEach(function(message) {
            setTimeout(function() {
                message.style.display = 'none';
            }, 5000);
        });
    });
    </script>
</body>
</html>

<?php include '../includes/footer.php'; ?>

