<?php
session_start();
include '../config.php';
include '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

// Get section for editing if ID is provided
$editing_section = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $sql = "SELECT * FROM homepage_sections WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editing_section = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $categories = isset($_POST['categories']) ? $_POST['categories'] : [];
                if (create_homepage_section(
                    $_POST['title'],
                    $_POST['section_type'],
                    $categories,
                    $_POST['layout_type'],
                    $_POST['posts_count']
                )) {
                    $message = "Section created successfully!";
                    $message_type = "success";
                } else {
                    $message = "Failed to create section.";
                    $message_type = "error";
                }
                break;

            case 'update':
                $section_id = $_POST['section_id'];
                $title = $_POST['title'];
                $section_type = $_POST['section_type'];
                $categories = isset($_POST['categories']) ? $_POST['categories'] : [];
                $layout_type = $_POST['layout_type'];
                $posts_count = $_POST['posts_count'];
                
                $sql = "UPDATE homepage_sections SET 
                        title = ?, 
                        section_type = ?, 
                        categories = ?, 
                        layout_type = ?, 
                        posts_count = ? 
                        WHERE id = ?";
                
                $stmt = $conn->prepare($sql);
                $categories_json = json_encode($categories);
                $stmt->bind_param("ssssii", $title, $section_type, $categories_json, $layout_type, $posts_count, $section_id);
                
                if ($stmt->execute()) {
                    $message = "Section updated successfully!";
                    $message_type = "success";
                    // Redirect to remove the edit parameter
                    header('Location: manage_sections.php');
                    exit;
                } else {
                    $message = "Failed to update section.";
                    $message_type = "error";
                }
                break;

            case 'update_order':
                if (update_sections_order($_POST['order'])) {
                    $message = "Section order updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Failed to update section order.";
                    $message_type = "error";
                }
                break;

            case 'toggle':
                if (toggle_section($_POST['section_id'])) {
                    $message = "Section status updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Failed to update section status.";
                    $message_type = "error";
                }
                break;

            case 'delete':
                if (delete_section($_POST['section_id'])) {
                    $message = "Section deleted successfully!";
                    $message_type = "success";
                } else {
                    $message = "Failed to delete section.";
                    $message_type = "error";
                }
                break;
        }
    }
}

$sections = get_homepage_sections(true); // Get all sections including inactive ones
$categories = get_categories();

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Homepage Sections - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Manage Homepage Sections</h1>
            <a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Create/Edit Section Form -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                    <?php echo $editing_section ? 'Edit Section: ' . htmlspecialchars($editing_section['title']) : 'Add New Section'; ?>
                </h2>
                <form action="manage_sections.php" method="post" class="space-y-4">
                    <input type="hidden" name="action" value="<?php echo $editing_section ? 'update' : 'create'; ?>">
                    <?php if ($editing_section): ?>
                        <input type="hidden" name="section_id" value="<?php echo $editing_section['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Section Title</label>
                            <input type="text" name="title" required
                                   value="<?php echo $editing_section ? htmlspecialchars($editing_section['title']) : ''; ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Section Type</label>
                            <select name="section_type" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                    onchange="toggleCategorySelect(this.value)">
                                <option value="featured" <?php echo ($editing_section && $editing_section['section_type'] === 'featured') ? 'selected' : ''; ?>>Featured Posts</option>
                                <option value="latest" <?php echo ($editing_section && $editing_section['section_type'] === 'latest') ? 'selected' : ''; ?>>Latest Posts</option>
                                <option value="popular" <?php echo ($editing_section && $editing_section['section_type'] === 'popular') ? 'selected' : ''; ?>>Popular Posts</option>
                                <option value="category" <?php echo ($editing_section && $editing_section['section_type'] === 'category') ? 'selected' : ''; ?>>Category Posts</option>
                                <option value="random" <?php echo ($editing_section && $editing_section['section_type'] === 'random') ? 'selected' : ''; ?>>Random Posts</option>
                                <option value="custom" <?php echo ($editing_section && $editing_section['section_type'] === 'custom') ? 'selected' : ''; ?>>Custom Posts</option>
                            </select>
                        </div>

                        <div id="categorySelect" style="display: <?php echo ($editing_section && $editing_section['section_type'] === 'category') ? 'block' : 'none'; ?>;">
                            <label class="block text-sm font-medium text-gray-700">Categories</label>
                            <select name="categories[]" multiple
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 select2-multiple">
                                <?php foreach ($categories as $category): 
                                    $selected = $editing_section && 
                                              $editing_section['categories'] && 
                                              in_array($category['id'], json_decode($editing_section['categories'], true));
                                ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $selected ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Layout Type</label>
                            <select name="layout_type" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                <option value="grid" <?php echo ($editing_section && $editing_section['layout_type'] === 'grid') ? 'selected' : ''; ?>>Grid</option>
                                <option value="list" <?php echo ($editing_section && $editing_section['layout_type'] === 'list') ? 'selected' : ''; ?>>List</option>
                                <option value="carousel" <?php echo ($editing_section && $editing_section['layout_type'] === 'carousel') ? 'selected' : ''; ?>>Carousel</option>
                                <option value="masonry" <?php echo ($editing_section && $editing_section['layout_type'] === 'masonry') ? 'selected' : ''; ?>>Masonry</option>
                                <option value="featured" <?php echo ($editing_section && $editing_section['layout_type'] === 'featured') ? 'selected' : ''; ?>>Featured</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Number of Posts</label>
                            <input type="number" name="posts_count" required min="1" max="12" 
                                   value="<?php echo $editing_section ? $editing_section['posts_count'] : '4'; ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <?php if ($editing_section): ?>
                            <a href="manage_sections.php" class="mr-2 bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Cancel
                            </a>
                        <?php endif; ?>
                        <button type="submit"
                                class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                            <?php echo $editing_section ? 'Update Section' : 'Add Section'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sections List -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Homepage Sections</h2>
                <div class="space-y-4" id="sectionsList">
                    <?php foreach ($sections as $section): ?>
                        <div class="border rounded-lg p-4 bg-gray-50 flex items-center justify-between" 
                             data-id="<?php echo $section['id']; ?>">
                            <div class="flex items-center space-x-4">
                                <button class="handle cursor-move text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-grip-vertical"></i>
                                </button>
                                <div>
                                    <h3 class="font-medium text-gray-900">
                                        <?php echo htmlspecialchars($section['title']); ?>
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        <?php echo ucfirst($section['section_type']); ?> - 
                                        <?php echo ucfirst($section['layout_type']); ?> - 
                                        <?php echo $section['posts_count']; ?> posts
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <a href="?edit=<?php echo $section['id']; ?>" class="text-blue-600 hover:text-blue-700">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="manage_sections.php" method="post" class="inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                                    <button type="submit" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-<?php echo $section['is_active'] ? 'eye' : 'eye-slash'; ?>"></i>
                                    </button>
                                </form>
                                <form action="manage_sections.php" method="post" class="inline" 
                                      onsubmit="return confirm('Are you sure you want to delete this section?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-700">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleCategorySelect(value) {
            const categorySelect = document.getElementById('categorySelect');
            categorySelect.style.display = value === 'category' ? 'block' : 'none';
        }

        // Initialize Select2 for multiple select
        $(document).ready(function() {
            $('.select2-multiple').select2();
        });

        // Initialize drag and drop functionality
        const sectionsList = document.getElementById('sectionsList');
        new Sortable(sectionsList, {
            animation: 150,
            handle: '.handle',
            ghostClass: 'bg-purple-50',
            onEnd: function() {
                const sections = Array.from(sectionsList.children);
                const order = sections.map(section => section.dataset.id);
                
                fetch('manage_sections.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_order&order=${JSON.stringify(order)}`
                });
            }
        });
    </script>
</body>
</html>

<?php include '../includes/footer.php'; ?>

