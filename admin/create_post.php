<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../config.php';
include '../includes/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Get categories using the correct function name from functions.php
$categories = get_categories();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate required fields
        if (empty($_POST['title']) || empty($_POST['content'])) {
            throw new Exception("Title and content are required fields.");
        }

        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $meta_title = trim($_POST['meta_title'] ?? '');
        $meta_description = trim($_POST['meta_description'] ?? '');
        $focus_keyword = trim($_POST['focus_keyword'] ?? '');
        $custom_slug = trim($_POST['slug'] ?? '');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $custom_order = isset($_POST['custom_order']) ? intval($_POST['custom_order']) : 0;
        
        // Handle file upload
        $featured_image = '';
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = realpath(__DIR__ . '/../uploads/');
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generate unique filename
            $file_info = pathinfo($_FILES['featured_image']['name']);
            $file_extension = strtolower($file_info['extension']);
            $file_name = uniqid('post_', true) . '.' . $file_extension;
            $upload_path = $upload_dir . '/' . $file_name;

            // Validate file type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception("Invalid file type. Allowed types: " . implode(', ', $allowed_types));
            }

            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_path)) {
                $featured_image = 'uploads/' . $file_name;
            } else {
                throw new Exception("Failed to upload image: " . error_get_last()['message']);
            }
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            // Create post
            $post_id = create_post(
                $title,
                $content,
                $_SESSION['user_id'],
                $meta_title,
                $meta_description,
                $focus_keyword,
                $custom_slug,
                $featured_image,
                $is_featured,
                $custom_order
            );

            if (!$post_id) {
                throw new Exception("Failed to create post");
            }

            // Log the post ID for debugging
            error_log("Post created successfully with ID: " . $post_id);

            // Add categories if selected
            if (!empty($_POST['categories'])) {
                foreach ($_POST['categories'] as $category_id) {
                    if (!add_post_category($post_id, $category_id)) {
                        throw new Exception("Failed to add category to post");
                    }
                }
            }

            // Commit transaction
            $conn->commit();

            $message = "Post created successfully!";
            $message_type = 'success';

            // Redirect to edit page
            header("Location: edit_post.php?id=" . $post_id);
            exit();

        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn)) {
            $conn->rollback();
        }

        // Delete uploaded image if it exists
        if (!empty($featured_image) && file_exists($upload_dir . '/' . $file_name)) {
            unlink($upload_dir . '/' . $file_name);
        }

        $message = $e->getMessage();
        $message_type = 'error';
        error_log('Error creating post: ' . $message);
    }
}

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tiny.cloud/1/c5cufszlvbvrg1fmxqlwssnx4yme3d7zj7gok843n9dkruo4/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/29.0.0/classic/ckeditor.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Create New Post</h1>
            <a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div class="mb-4 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data" class="space-y-6" id="createPostForm">
            <!-- Main Content Section -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4">Main Content</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                                Title <span class="text-red-500">*</span>
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                   id="title" 
                                   type="text" 
                                   name="title" 
                                   required>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="content">
                                Content <span class="text-red-500">*</span>
                            </label>
                            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                      id="content" 
                                      name="content" 
                                      rows="10" 
                                      required></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories Section -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4">Categories</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="categories">
                                Select Categories
                            </label>
                            <select name="categories[]" 
                                    id="categories" 
                                    multiple 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">Hold Ctrl (Windows) or Command (Mac) to select multiple categories</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEO Section -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4">SEO Settings</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="slug">URL Slug</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                   id="slug" 
                                   type="text" 
                                   name="slug" 
                                   placeholder="Leave blank to auto-generate from title">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="meta_title">Meta Title</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                   id="meta_title" 
                                   type="text" 
                                   name="meta_title">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="meta_description">Meta Description</label>
                            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                      id="meta_description" 
                                      name="meta_description" 
                                      rows="3"></textarea>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="focus_keyword">Focus Keyword</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                   id="focus_keyword" 
                                   type="text" 
                                   name="focus_keyword">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Featured Image Section -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4">Featured Image</h2>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="featured_image">
                            Upload Featured Image
                        </label>
                        <input type="file" 
                               name="featured_image" 
                               id="featured_image" 
                               accept="image/*" 
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                </div>
            </div>

            <!-- Additional Options -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4">Additional Options</h2>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="is_featured" 
                                   name="is_featured" 
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <label class="ml-2 text-sm font-medium text-gray-700" for="is_featured">
                                Featured Post
                            </label>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="custom_order">
                                Custom Order
                            </label>
                            <input type="number" 
                                   id="custom_order" 
                                   name="custom_order" 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editor_choice">
                                Editor Choice
                            </label>
                            <select id="editor_choice" 
                                    name="editor_choice" 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="tinymce">TinyMCE</option>
                                <option value="ckeditor">CKEditor</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" 
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition-colors duration-300">
                    Create Post
                </button>
            </div>
        </form>
    </div>

    <script>
        let currentEditor = 'tinymce';
        let ckEditorInstance = null;

        function initTinyMCE() {
            if (ckEditorInstance) {
                ckEditorInstance.destroy()
                    .then(() => {
                        ckEditorInstance = null;
                    })
                    .catch(error => {
                        console.error("Error destroying CKEditor:", error);
                    });
            }

            tinymce.init({
                selector: '#content',
                plugins: 'advlist autolink lists link image charmap print preview hr anchor pagebreak',
                toolbar_mode: 'floating',
                height: 500,
                setup: function(editor) {
                    currentEditor = 'tinymce';
                    editor.on('change', function() {
                        editor.save();
                    });
                }
            });
        }

        function initCKEditor() {
            if (currentEditor === 'tinymce') {
                tinymce.remove('#content');
            }

            if (!ckEditorInstance) {
                ClassicEditor
                    .create(document.querySelector('#content'))
                    .then(editor => {
                        ckEditorInstance = editor;
                        currentEditor = 'ckeditor';
                    })
                    .catch(error => {
                        console.error("Error initializing CKEditor:", error);
                    });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('createPostForm');
            const editorChoice = document.getElementById('editor_choice');
            const submitButton = document.querySelector('button[type="submit"]');

            // Initialize default editor
            initTinyMCE();

            // Editor choice change handler
            editorChoice.addEventListener('change', function() {
                if (this.value === 'tinymce') {
                    initTinyMCE();
                } else {
                    initCKEditor();
                }
            });
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const title = document.getElementById('title').value.trim();
                let content = '';
                
                if (currentEditor === 'tinymce') {
                    content = tinymce.get('content').getContent().trim();
                } else if (ckEditorInstance) {
                    content = ckEditorInstance.getData().trim();
                }
                
                if (!title || !content) {
                    alert('Please fill in all required fields (Title and Content)');
                    return;
                }
                
                this.submit();
            });
        });
    </script>
</body>
</html>

<?php include '../includes/footer.php'; ?>

