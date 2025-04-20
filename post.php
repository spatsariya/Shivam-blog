<?php
// Remove extraction of "id" and only grab the slug from the query string
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

// If no slug is provided then error out
if (empty($slug)) {
    http_response_code(404);
    include '404.php';
    exit;
}

include 'config.php';
include 'includes/functions.php';

try {
    // Debug logging
    debug_log('Post page accessed with slug: ' . $slug);

    // Get the post using the slug
    $post = get_post_by_slug($slug);

    // Debug logging and error handling
    if (!$post) {
        debug_log('No post found for slug: ' . $slug);
        throw new Exception('Post not found');
    }

    // Increment view count - don't throw if this fails
    increment_post_view_count($post['id']);

    // Get post metadata
    $post_categories = get_post_categories($post['id']);
    $category_names = array_column($post_categories, 'name');

    // Get post image
    $post_image = get_post_image($post);

    // Get engagement counts
    $engagement = get_post_engagement_counts($post['id']);
    $post['view_count'] = $engagement['view_count'];
    $post['like_count'] = $engagement['like_count'];
    $post['comment_count'] = $engagement['comment_count'];

    // Get next and previous posts
    $next_post = get_adjacent_post($post['id'], 'next');
    $prev_post = get_adjacent_post($post['id'], 'prev');

    // Set meta tags
    $page_title = !empty($post['meta_title']) ? $post['meta_title'] : $post['title'];
    $page_description = !empty($post['meta_description']) ? $post['meta_description'] : get_excerpt($post['content'], 160);

    // Get comments
    $comments = get_post_comments($post['id']);

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-50">
    <main class="container mx-auto px-4 py-8">
        <article class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
            <?php if ($post_image): ?>
            <div class="relative h-96 w-full">
                <img 
                    src="<?php echo htmlspecialchars($post_image); ?>" 
                    alt="<?php echo htmlspecialchars($post['title']); ?>"
                    class="w-full h-full object-cover"
                >
            </div>
            <?php endif; ?>

            <div class="p-8">
                <!-- Categories -->
                <div class="flex gap-2 mb-4">
                    <?php foreach ($post_categories as $category): ?>
                    <a href="/category/<?php echo $category['id']; ?>" 
                       class="text-sm font-medium text-blue-600 hover:text-blue-800">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Title -->
                <h1 class="text-4xl font-bold text-gray-900 mb-4">
                    <?php echo htmlspecialchars($post['title']); ?>
                </h1>

                <!-- Metadata -->
                <div class="flex items-center gap-4 text-sm text-gray-600 mb-8">
                    <time datetime="<?php echo $post['created_at']; ?>">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        <?php echo format_date($post['created_at']); ?>
                    </time>
                    <span>
                        <i class="fas fa-eye mr-1"></i>
                        <?php echo number_format($post['view_count']); ?> views
                    </span>
                    <span>
                        <i class="fas fa-heart mr-1"></i>
                        <span id="like-count"><?php echo number_format($post['like_count']); ?></span> likes
                    </span>
                    <span class="comment-count">
                        <i class="fas fa-comment mr-1"></i>
                        <?php echo number_format($post['comment_count']); ?> comments
                    </span>
                </div>

                <!-- Content -->
                <div class="prose prose-lg max-w-none mb-8">
                    <?php echo $post['content']; ?>
                </div>

                <!-- Like and Share buttons -->
                <div class="flex items-center gap-4 mb-8">
                    <button id="likeButton" class="bg-blue-500 text-white px-4 py-2 rounded-full hover:bg-blue-600 transition-colors">
                        <i class="fas fa-heart mr-2"></i> Like
                    </button>
                    <button id="shareButton" class="bg-green-500 text-white px-4 py-2 rounded-full hover:bg-green-600 transition-colors">
                        <i class="fas fa-share-alt mr-2"></i> Share
                    </button>
                </div>

                <!-- Next/Previous Post Navigation -->
                <div class="flex justify-between items-center border-t border-b border-gray-200 py-4 mb-8">
                    <?php if ($prev_post): ?>
                    <a href="/post/<?php echo $prev_post['slug']; ?>" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left mr-2"></i> Previous Post
                    </a>
                    <?php endif; ?>
                    <?php if ($next_post): ?>
                    <a href="/post/<?php echo $next_post['slug']; ?>" class="text-blue-600 hover:text-blue-800 ml-auto">
                        Next Post <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Comments Section -->
                <div id="comments" class="mt-8">
                    <h2 class="text-2xl font-bold mb-4">Comments</h2>
                    <!-- Comment Form -->
                    <form id="commentForm" class="mb-8">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" id="name" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        </div>
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" id="email" name="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        </div>
                        <div class="mb-4">
                            <label for="comment" class="block text-sm font-medium text-gray-700">Comment</label>
                            <textarea id="comment" name="comment" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"></textarea>
                        </div>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition-colors">Post Comment</button>
                    </form>
                    <!-- Comments List -->
                    <div id="commentsList">
                        <?php if (empty($comments)): ?>
                            <p class="text-gray-600">No comments yet. Be the first to comment!</p>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-bold"><?php echo htmlspecialchars($comment['name']); ?></span>
                                    <span class="text-sm text-gray-500"><?php echo format_date($comment['created_at']); ?></span>
                                </div>
                                <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </article>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
    $(document).ready(function() {
        const postId = <?php echo $post['id']; ?>;

        // Like functionality
        $('#likeButton').click(function() {
            $.post('/ajax/like_post.php', { post_id: postId }, function(response) {
                if (response.success) {
                    $('#like-count').text(response.likes);
                } else {
                    alert('Error: ' + response.message);
                }
            });
        });

        // Share functionality
        $('#shareButton').click(function() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($post['title']); ?>',
                    url: window.location.href
                }).then(() => {
                    console.log('Thanks for sharing!');
                }).catch(console.error);
            } else {
                // Fallback for unsupported browsers
                prompt('Copy this link to share:', window.location.href);
            }
        });

        // Comment submission
        $('#commentForm').submit(function(e) {
            e.preventDefault();

            $.ajax({
                url: '/ajax/add_comment.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json', // Explicitly set dataType to json
                success: function(response) {
                    if (response.success) {
                        // Add the new comment to the list
                        $('#commentsList').prepend(
                            '<div class="bg-gray-50 p-4 rounded-lg mb-4">' +
                            '<div class="flex justify-between items-center mb-2">' +
                            '<span class="font-bold">' + response.comment.name + '</span>' +
                            '<span class="text-sm text-gray-500">' + response.comment.created_at + '</span>' +
                            '</div>' +
                            '<p>' + response.comment.content + '</p>' +
                            '</div>'
                        );
                        // Clear the form
                        $('#commentForm')[0].reset();
                        // Update comment count
                        var currentCount = parseInt($('.comment-count').text().split(' ')[0]);
                        $('.comment-count').text((currentCount + 1) + ' comments');
                    } else {
                        console.error('Error adding comment:', response.message);
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', xhr.responseText);
                    alert('An error occurred while submitting the comment. Please check the console for details.');
                }
            });
        });
    });
    </script>
</body>
</html>

<?php
} catch (Exception $e) {
    // Log the error
    error_log("Error in post.php: " . $e->getMessage());
    
    // Show 404 page
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}
?>