<?php
// Get dynamic meta tags
$page_title = isset($page_title) ? $page_title . ' - Your Blog' : 'Your Blog';
$page_description = isset($page_description) ? $page_description : 'Explore the latest articles on various topics at Your Blog.';

// Get navigation menu items
$nav_menu_items = get_nav_menu_items();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 leading-normal">
    <header class="bg-white border-b sticky top-0 z-50 shadow-sm">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="/" class="text-2xl font-bold text-blue-600 hover:text-blue-700 transition-colors">Your Blog</a>
                </div>

                <!-- Main Navigation -->
                <div class="hidden md:flex md:items-center md:space-x-8">
                    <?php 
                    $current_page = basename($_SERVER['PHP_SELF']);
                    foreach ($nav_menu_items as $item): 
                        $category_ids = explode(',', $item['category_ids']);
                        $href = count($category_ids) === 1 ? "/category/{$category_ids[0]}" : "/category/" . implode(',', $category_ids);
                        $is_active = ($current_page === 'index.php' && $item['label'] === 'Home') || 
                                     (strpos($current_page, 'category.php') !== false && in_array($_GET['id'] ?? '', $category_ids));
                    ?>
                        <a href="<?php echo $href; ?>" class="text-gray-600 hover:text-blue-600 px-3 py-2 text-sm font-medium rounded-md transition-colors <?php echo $is_active ? 'bg-blue-50 text-blue-600' : ''; ?>">
                            <?php echo htmlspecialchars($item['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Search and User Menu -->
                <div class="hidden md:flex md:items-center md:space-x-4">
                    <!-- Search Bar -->
                    <form action="/search.php" method="GET" class="relative">
                        <input 
                            type="search" 
                            name="q" 
                            placeholder="Search..." 
                            class="w-64 px-4 py-2 border border-gray-300 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                        <button type="submit" class="absolute right-0 top-0 mt-2 mr-4 text-gray-400 hover:text-blue-500">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>

                    <!-- User Menu -->
                    <?php if (is_logged_in()): ?>
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 focus:outline-none focus:text-blue-600" id="user-menu" aria-haspopup="true">
                                <span class="mr-2"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5" role="menu" aria-orientation="vertical" aria-labelledby="user-menu">
                                <a href="/admin/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Dashboard</a>
                                <a href="/admin/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">My Account</a>
                                <a href="/admin/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="/admin/login.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Login
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" aria-controls="mobile-menu" aria-expanded="false" id="mobile-menu-button">
                        <span class="sr-only">Open main menu</span>
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile menu -->
            <div class="md:hidden hidden" id="mobile-menu">
                <div class="pt-2 pb-3 space-y-1">
                    <?php foreach ($nav_menu_items as $item): 
                        $category_ids = explode(',', $item['category_ids']);
                        $href = count($category_ids) === 1 ? "/category/{$category_ids[0]}" : "/category/" . implode(',', $category_ids);
                    ?>
                        <a href="<?php echo $href; ?>" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                            <?php echo htmlspecialchars($item['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Mobile Search -->
                <div class="pt-4 pb-3 border-t border-gray-200">
                    <form action="/search.php" method="GET" class="relative mx-3">
                        <input 
                            type="search" 
                            name="q" 
                            placeholder="Search..." 
                            class="w-full px-4 py-2 border border-gray-300 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                        <button type="submit" class="absolute right-0 top-0 mt-2 mr-4 text-gray-400 hover:text-blue-500">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>

                <!-- Mobile User Menu -->
                <div class="pt-4 pb-3 border-t border-gray-200">
                    <?php if (is_logged_in()): ?>
                        <div class="px-4 py-2 text-sm text-gray-700">
                            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </div>
                        <a href="/admin/index.php" class="block px-4 py-2 text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                            Dashboard
                        </a>
                        <a href="/admin/profile.php" class="block px-4 py-2 text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                            My Account
                        </a>
                        <a href="/admin/logout.php" class="block px-4 py-2 text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                            Logout
                        </a>
                    <?php else: ?>
                        <a href="/admin/login.php" class="block px-4 py-2 text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                            Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
</body>
</html>

