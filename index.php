<?php
include 'config.php';
include 'includes/functions.php';
include 'includes/debug.php';

// Get all active homepage sections
$sections = get_homepage_sections();

// Set meta tags for the homepage
$page_title = 'Welcome to Your Blog';
$page_description = 'Discover a world of insightful articles and engaging content on various topics at Your Blog.';

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    <script src="https://unpkg.com/masonry-layout@4/dist/masonry.pkgd.min.js"></script>
</head>
<body class="bg-gray-50">
    <main class="container mx-auto px-4 py-8">
        <?php if (empty($sections)): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-8">
                No homepage sections found. Please add sections in the admin panel.
            </div>
        <?php else: ?>
            <?php foreach ($sections as $section): 
                $posts = get_section_posts($section);
            ?>
                <section class="mb-12">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($section['title'] ?? ''); ?></h2>
                        <?php if (count($posts) > 4): ?>
                            <a href="/category/<?php echo urlencode($section['categories'] ?? ''); ?>" class="text-blue-600 hover:text-blue-800">View All</a>
                        <?php endif; ?>
                    </div>

                    <?php if ($section['layout_type'] === 'carousel'): ?>
                        <div class="owl-carousel">
                    <?php elseif ($section['layout_type'] === 'grid'): ?>
                        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                    <?php elseif ($section['layout_type'] === 'list'): ?>
                        <div class="space-y-8">
                    <?php elseif ($section['layout_type'] === 'masonry'): ?>
                        <div class="masonry-grid">
                    <?php elseif ($section['layout_type'] === 'featured'): ?>
                        <div class="grid gap-8 md:grid-cols-2">
                    <?php endif; ?>

                    <?php foreach ($posts as $index => $post): 
                        $post_image = function_exists('get_post_image') ? get_post_image($post) : '';
                        debug_log('Post image URL', $post_image);
                        $categories = function_exists('get_post_categories') ? get_post_categories($post['id']) : [];
                    ?>
                        <article class="<?php echo $section['layout_type'] === 'list' ? 'flex gap-6' : ''; ?> bg-white rounded-lg shadow-md overflow-hidden transition-transform duration-300 hover:-translate-y-1 <?php echo ($section['layout_type'] === 'featured' && $index === 0) ? 'md:col-span-2' : ''; ?>">
                            <div class="<?php echo $section['layout_type'] === 'list' ? 'w-1/3' : ''; ?>">
                                <?php if ($post_image): ?>
                                    <img 
                                        src="<?php echo htmlspecialchars($post_image); ?>" 
                                        alt="<?php echo htmlspecialchars($post['title'] ?? ''); ?>"
                                        class="object-cover w-full <?php echo $section['layout_type'] === 'featured' ? 'h-64' : 'h-48'; ?>"
                                        onerror="this.onerror=null; this.src='/path/to/fallback-image.jpg'; console.error('Image failed to load:', this.src);"
                                    >
                                    <?php debug_log('Displaying image', $post_image); ?>
                                <?php else: ?>
                                    <div class="bg-gray-200 flex items-center justify-center <?php echo $section['layout_type'] === 'featured' ? 'h-64' : 'h-48'; ?>">
                                        <span class="text-gray-500">No image available</span>
                                    </div>
                                    <?php debug_log('No image available for post', $post['id']); ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="p-4 <?php echo $section['layout_type'] === 'list' ? 'w-2/3' : ''; ?>">
                                <?php if (!empty($categories)): ?>
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        <?php foreach ($categories as $category): ?>
                                            <a href="/category/<?php echo $category['id']; ?>" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                                <?php echo htmlspecialchars($category['name'] ?? ''); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <h3 class="text-xl font-semibold mb-2">
                                    <a href="/post/<?php echo urlencode($post['slug'] ?? ''); ?>" class="text-gray-900 hover:text-blue-600 transition-colors">
                                        <?php echo htmlspecialchars($post['title'] ?? ''); ?>
                                    </a>
                                </h3>
                                <p class="text-gray-600 mb-4"><?php echo get_excerpt($post['content'] ?? '', 150); ?></p>
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <span><?php echo isset($post['created_at']) ? date('F j, Y', strtotime($post['created_at'])) : ''; ?></span>
                                    <div>
                                        <span class="mr-2"><i class="fas fa-eye"></i> <?php echo $post['view_count'] ?? 0; ?></span>
                                        <span class="mr-2"><i class="fas fa-heart"></i> <?php echo $post['like_count'] ?? 0; ?></span>
                                        <span><i class="fas fa-comment"></i> <?php echo $post['comment_count'] ?? 0; ?></span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    </div>

                    <?php if ($section['layout_type'] === 'carousel'): ?>
                        <script>
                            $(document).ready(function(){
                                $(".owl-carousel").owlCarousel({
                                    loop: true,
                                    margin: 20,
                                    nav: true,
                                    responsive: {
                                        0: { items: 1 },
                                        600: { items: 2 },
                                        1000: { items: 3 }
                                    }
                                });
                            });
                        </script>
                    <?php elseif ($section['layout_type'] === 'masonry'): ?>
                        <script>
                            $(document).ready(function(){
                                $('.masonry-grid').masonry({
                                    itemSelector: 'article',
                                    columnWidth: 'article',
                                    percentPosition: true
                                });
                            });
                        </script>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>

