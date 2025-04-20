<?php
include 'config.php';
include 'includes/functions.php';

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$posts = [];

if (!empty($search_query)) {
    $posts = search_posts($search_query);
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Your Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <main class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold mb-8 text-center text-gray-900">Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h1>
        
        <?php if (empty($posts)): ?>
            <p class="text-center text-gray-600 text-lg">No posts found matching your search query.</p>
        <?php else: ?>
            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($posts as $post): 
                    $categories = get_post_categories($post['id']);
                    $first_image = get_first_image_url($post['content']);
                ?>
                    <article class="bg-white rounded-lg shadow-md overflow-hidden transition-transform duration-300 hover:-translate-y-1">
                        <?php if ($first_image): ?>
                            <div class="aspect-w-16 aspect-h-9">
                                <img 
                                    src="<?php echo htmlspecialchars($first_image); ?>" 
                                    alt="<?php echo htmlspecialchars($post['title']); ?>"
                                    class="object-cover w-full h-48"
                                >
                            </div>
                        <?php endif; ?>
                        
                        <div class="p-6">
                            <!-- Categories -->
                            <?php if (!empty($categories)): ?>
                                <div class="flex flex-wrap gap-2 mb-3">
                                    <?php foreach ($categories as $category): ?>
                                        <a 
                                            href="/category/<?php echo $category['id']; ?>"
                                            class="text-xs font-semibold bg-blue-100 text-blue-600 px-2 py-1 rounded-full hover:bg-blue-200 transition-colors"
                                        >
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Title -->
                            <h2 class="text-2xl font-semibold mb-3 text-gray-900 hover:text-blue-600 transition-colors">
                                <a href="/post/<?php echo $post['slug']; ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h2>

                            <!-- Excerpt -->
                            <p class="text-gray-600 mb-4">
                                <?php echo get_excerpt($post['content'], 150); ?>
                            </p>

                            <!-- Footer -->
                            <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-100">
                                <span class="text-sm text-gray-500">
                                    <time datetime="<?php echo $post['created_at']; ?>">
                                        <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                                    </time>
                                </span>
                                <a 
                                    href="/post/<?php echo $post['slug']; ?>" 
                                    class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium"
                                >
                                    Read more
                                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>

<?php include 'includes/footer.php'; ?>

