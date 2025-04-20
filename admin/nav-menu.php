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
        switch ($_POST['action']) {
            case 'create':
                if (!empty($_POST['label']) && !empty($_POST['category_ids'])) {
                    $label = $_POST['label'];
                    $category_ids = $_POST['category_ids'];
                    $order_index = isset($_POST['order_index']) ? (int)$_POST['order_index'] : 0;

                    if (create_nav_menu_item($label, $category_ids, $order_index)) {
                        $message = "Nav menu item created successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Failed to create nav menu item.";
                        $message_type = "error";
                    }
                } else {
                    $message = "Please fill in all fields.";
                    $message_type = "error";
                }
                break;
            case 'delete':
                if (!empty($_POST['id'])) {
                    if (delete_nav_menu_item($_POST['id'])) {
                        $message = "Nav menu item deleted successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Failed to delete nav menu item.";
                        $message_type = "error";
                    }
                } else {
                    $message = "Invalid request.";
                    $message_type = "error";
                }
                break;
            case 'update_order':
                if (!empty($_POST['order'])) {
                    if (update_nav_menu_order($_POST['order'])) {
                        $message = "Nav menu order updated successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Failed to update nav menu order.";
                        $message_type = "error";
                    }
                } else {
                    $message = "Invalid order data.";
                    $message_type = "error";
                }
                break;
        }
    }
}

$nav_menu_items = get_nav_menu_items();
$categories = get_categories();

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Nav Menu - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex justify-between items-center">
                    <h1 class="text-3xl font-bold text-gray-900">Manage Nav Menu</h1>
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

            <!-- Create Nav Menu Item Form -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Create New Nav Menu Item</h2>
                    <form action="nav-menu.php" method="post" class="space-y-4">
                        <input type="hidden" name="action" value="create">
                        <div>
                            <label for="label" class="block text-sm font-medium text-gray-700">Label</label>
                            <input type="text" id="label" name="label" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="category_ids" class="block text-sm font-medium text-gray-700">Categories</label>
                            <select id="category_ids" name="category_ids[]" multiple required
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                                    size="5">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-2 text-sm text-gray-500">Hold Ctrl (Windows) or Command (Mac) to select multiple categories</p>
                        </div>
                        <div>
                            <label for="order_index" class="block text-sm font-medium text-gray-700">Order</label>
                            <input type="number" id="order_index" name="order_index" value="0" min="0"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-plus mr-2"></i> Create Nav Menu Item
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Nav Menu Items List -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Existing Nav Menu Items</h2>
                    <?php if (empty($nav_menu_items)): ?>
                        <p class="text-gray-500 text-center py-4">No nav menu items found. Create your first item above.</p>
                    <?php else: ?>
                        <ul id="nav-menu-list" class="space-y-2">
                            <?php foreach ($nav_menu_items as $item): ?>
                                <li class="flex items-center justify-between p-4 bg-gray-50 rounded-lg" data-id="<?php echo $item['id']; ?>">
                                    <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($item['label']); ?></span>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm text-gray-500"><?php echo htmlspecialchars($item['category_names']); ?></span>
                                        <button class="text-gray-400 hover:text-gray-500 handle cursor-move">
                                            <i class="fas fa-grip-vertical"></i>
                                        </button>
                                        <form action="nav-menu.php" method="post" class="inline" onsubmit="return confirm('Are you sure you want to delete this nav menu item?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const list = document.getElementById('nav-menu-list');
            if (list) {
                new Sortable(list, {
                    animation: 150,
                    handle: '.handle',
                    onEnd: function() {
                        const items = list.querySelectorAll('li');
                        const order = Array.from(items).map(item => item.dataset.id);

                        fetch('nav-menu.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'action=update_order&order=' + JSON.stringify(order)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('Order updated successfully');
                            } else {
                                console.error('Failed to update order');
                            }
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>

<?php include '../includes/footer.php'; ?>

