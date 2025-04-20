<?php
include 'config.php';
include 'includes/functions.php';

header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?php echo 'https://' . $_SERVER['HTTP_HOST']; ?>/</loc>
        <lastmod><?php echo date('Y-m-d'); ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <?php
    $posts = get_posts(1000); // Adjust the number based on your needs
    foreach ($posts as $post):
    ?>
    <url>
        <loc><?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/post/' . $post['slug']; ?></loc>
        <lastmod><?php echo date('Y-m-d', strtotime($post['created_at'])); ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>
    
    <?php
    $categories = get_categories();
    foreach ($categories as $category):
    ?>
    <url>
        <loc><?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/category/' . $category['id']; ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
    <?php endforeach; ?>
</urlset>

