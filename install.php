<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enable error reporting for debugging during installation
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

session_start();

if (file_exists('config.php')) {
    die('The blog is already installed. Delete config.php to reinstall.');
}



$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

function display_header($title) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>$title</title>
        <link rel='stylesheet' href='style.css'>
    </head>
    <body>
        <div class='container'>
            <h1>$title</h1>";
}

function display_footer() {
    echo "</div></body></html>";
}

switch ($step) {
    case 1:
        display_header('Blog Installation - Step 1');
        echo "<form method='post' action='?step=2'>
            <h2>Database Information</h2>
            <label for='db_host'>Database Host:</label>
            <input type='text' id='db_host' name='db_host' value='localhost' required>
            
            <label for='db_name'>Database Name:</label>
            <input type='text' id='db_name' name='db_name' required>
            
            <label for='db_user'>Database User:</label>
            <input type='text' id='db_user' name='db_user' required>
            
            <label for='db_pass'>Database Password:</label>
            <input type='password' id='db_pass' name='db_pass' required>
            
            <h2>Admin User</h2>
            <label for='admin_user'>Admin Username:</label>
            <input type='text' id='admin_user' name='admin_user' required>
            
            <label for='admin_pass'>Admin Password:</label>
            <input type='password' id='admin_pass' name='admin_pass' required>
            
            <label for='admin_email'>Admin Email:</label>
            <input type='text' id='admin_email' name='admin_email' required>
            
            <input type='submit' value='Install'>
        </form>";
        display_footer();
        break;
    
    case 2:
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $_SESSION['install_data'] = $_POST;
                
                $db_host = $_POST['db_host'];
                $db_name = $_POST['db_name'];
                $db_user = $_POST['db_user'];
                $db_pass = $_POST['db_pass'];
                
                // Try to connect to the database
                $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
                
                if ($conn->connect_error) {
                    throw new Exception("Database connection failed: " . $conn->connect_error);
                }
                
                // Set charset
                $conn->set_charset("utf8mb4");
                
                // Create tables
                $tables = [
                    "CREATE TABLE users (
                        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        username VARCHAR(50) NOT NULL UNIQUE,
                        password VARCHAR(255) NOT NULL,
                        email VARCHAR(100) NOT NULL UNIQUE,
                        role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )",
                    "CREATE TABLE posts (
                        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        title VARCHAR(255) NOT NULL,
                        slug VARCHAR(255) NOT NULL UNIQUE,
                        content TEXT NOT NULL,
                        feature_image VARCHAR(255),
                        user_id INT(11) UNSIGNED,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        meta_title VARCHAR(255),
                        meta_description TEXT,
                        focus_keyword VARCHAR(255),
                        FOREIGN KEY (user_id) REFERENCES users(id)
                    )",
                    "CREATE TABLE categories (
                        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(50) NOT NULL UNIQUE,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )",
                    "CREATE TABLE post_categories (
                        post_id INT(11) UNSIGNED,
                        category_id INT(11) UNSIGNED,
                        PRIMARY KEY (post_id, category_id),
                        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
                    )",
                    "CREATE TABLE nav_menu_items (
                        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        label VARCHAR(255) NOT NULL,
                        order_index INT(11) NOT NULL DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )",
                    "CREATE TABLE nav_menu_item_categories (
                        nav_menu_item_id INT(11) UNSIGNED,
                        category_id INT(11) UNSIGNED,
                        PRIMARY KEY (nav_menu_item_id, category_id),
                        FOREIGN KEY (nav_menu_item_id) REFERENCES nav_menu_items(id) ON DELETE CASCADE,
                        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
                    )",
                    "CREATE TABLE homepage_sections (
                        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        title VARCHAR(255) NOT NULL,
                        section_type ENUM('latest', 'popular', 'category', 'featured', 'random', 'custom') NOT NULL,
                        categories TEXT,
                        layout_type ENUM('grid', 'list', 'carousel', 'masonry', 'featured') NOT NULL,
                        posts_count INT(11) NOT NULL DEFAULT 4,
                        order_index INT(11) NOT NULL DEFAULT 0,
                        is_active TINYINT(1) NOT NULL DEFAULT 1,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )"
                ];
                
                foreach ($tables as $table) {
                    if (!$conn->query($table)) {
                        throw new Exception("Table creation failed: " . $conn->error);
                    }
                }
                
                // Insert admin user
                $admin_user = $_POST['admin_user'];
                $admin_pass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
                $admin_email = $_POST['admin_email'];
                
                $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("sss", $admin_user, $admin_pass, $admin_email);
                if (!$stmt->execute()) {
                    throw new Exception("Admin user creation failed: " . $stmt->error);
                }
                
                $stmt->close();
                $conn->close();
                
                // Create config file
                $config_content = "<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', '{$db_host}');
define('DB_NAME', '{$db_name}');
define('DB_USER', '{$db_user}');
define('DB_PASS', '{$db_pass}');

try {
    \$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if (\$conn->connect_error) {
        throw new Exception('Connection failed: ' . \$conn->connect_error);
    }

    // Set charset to ensure proper encoding
    \$conn->set_charset(\"utf8mb4\");
} catch (Exception \$e) {
    error_log(\$e->getMessage());
    die('A database error occurred. Please check the error logs for more information.');
}
";
                
                if (file_put_contents('config.php', $config_content) === false) {
                    throw new Exception("Failed to create config file. Please check file permissions.");
                }
                
                display_header('Installation Complete');
                echo "<p>The blog has been successfully installed!</p>";
                echo "<p><a href='index.php'>Go to homepage</a></p>";
                display_footer();
                
            } catch (Exception $e) {
                display_header('Installation Error');
                echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p><a href='?step=1'>Go back</a></p>";
                display_footer();
                exit;
            }
        } else {
            header('Location: ?step=1');
            exit;
        }
        break;
    
    default:
        header('Location: ?step=1');
        exit;
}
?>