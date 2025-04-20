<?php
function debug_log($message, $data = null) {
    $log_file = __DIR__ . '/../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}";
    if ($data !== null) {
        $log_message .= ": " . print_r($data, true);
    }
    error_log($log_message . PHP_EOL, 3, $log_file);
}

// Post related functions
function get_posts($limit = 10) {
    global $conn;
    $sql = "SELECT * FROM posts ORDER BY created_at DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_post($id) {
    global $conn;
    
    // Check if the featured_image column exists
    $result = $conn->query("SHOW COLUMNS FROM posts LIKE 'featured_image'");
    $featured_image_exists = $result->num_rows > 0;
    
    if ($featured_image_exists) {
        $sql = "SELECT id, title, slug, content, featured_image, meta_title, meta_description, focus_keyword, created_at, view_count, like_count, comment_count FROM posts WHERE id = ?";
    } else {
        $sql = "SELECT id, title, slug, content, meta_title, meta_description, focus_keyword, created_at, view_count, like_count, comment_count FROM posts WHERE id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}



// Update the create_post function to handle all required parameters
function create_post($title, $content, $user_id, $meta_title = '', $meta_description = '', $focus_keyword = '', $custom_slug = '', $featured_image = '', $is_featured = 0, $custom_order = 0) {
    global $conn;
    
    try {
        // Generate slug if not provided
        $slug = empty($custom_slug) ? get_unique_slug($title) : get_unique_slug($custom_slug);
        
        // Prepare the SQL query with all fields
        $sql = "INSERT INTO posts (
            title, 
            content, 
            user_id, 
            slug,
            meta_title, 
            meta_description, 
            focus_keyword, 
            featured_image,
            is_featured,
            custom_order,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ssisssssii", 
            $title,
            $content,
            $user_id,
            $slug,
            $meta_title,
            $meta_description,
            $focus_keyword,
            $featured_image,
            $is_featured,
            $custom_order
        );
        
        // Execute the query
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        // Get the ID of the newly created post
        $post_id = $conn->insert_id;
        
        if (!$post_id) {
            throw new Exception("Failed to get new post ID");
        }
        
        return $post_id;
        
    } catch (Exception $e) {
        error_log("Error creating post: " . $e->getMessage());
        throw $e; // Re-throw the exception to be caught by the calling code
    }
}




function update_post($id, $title, $content, $meta_title, $meta_description, $focus_keyword, $slug, $featured_image = null) {
    global $conn;
    $slug = empty($slug) ? get_unique_slug($title, $id) : get_unique_slug($slug, $id);
    $sql = "UPDATE posts SET title = ?, slug = ?, content = ?, meta_title = ?, meta_description = ?, focus_keyword = ?, featured_image = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssi", $title, $slug, $content, $meta_title, $meta_description, $focus_keyword, $featured_image, $id);
    return $stmt->execute();
}


function delete_post($id) {
    global $conn;
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Get post data first (for featured image)
        $post = get_post($id);
        if (!$post) {
            throw new Exception("Post not found");
        }
        
        // Delete featured image if it exists
        if (!empty($post['featured_image'])) {
            $image_path = realpath(__DIR__ . '/../' . $post['featured_image']);
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Delete post categories
        $stmt = $conn->prepare("DELETE FROM post_categories WHERE post_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Delete the post
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception("Failed to delete post");
        }
        
        // Commit transaction
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error deleting post: " . $e->getMessage());
        return false;
    }

}




function get_post_by_slug($identifier) {
    global $conn;
    debug_log("Attempting to get post with identifier: " . $identifier);
    
    // First try to get by slug
    $stmt = $conn->prepare("SELECT * FROM posts WHERE slug = ?");
    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    
    // If not found by slug and identifier is numeric, try by ID
    if (!$post && is_numeric($identifier)) {
        $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->bind_param("i", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $post = $result->fetch_assoc();
    }
    
    if (!$post) {
        debug_log("No post found with identifier: " . $identifier);
    } else {
        debug_log("Post found", $post);
    }
    
    return $post;
}






function get_posts_paginated($page = 1, $per_page = 10) {
  global $conn;
  $offset = ($page - 1) * $per_page;
  
  $sql = "SELECT * FROM posts ORDER BY created_at DESC LIMIT ? OFFSET ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $per_page, $offset);
  $stmt->execute();
  $result = $stmt->get_result();
  return $result->fetch_all(MYSQLI_ASSOC);
}

function get_post_count() {
  global $conn;
  $sql = "SELECT COUNT(*) as count FROM posts";
  $result = $conn->query($sql);
  $row = $result->fetch_assoc();
  return $row['count'];
}

function get_post_categories($post_id) {
  global $conn;
  $sql = "SELECT c.* FROM categories c 
          JOIN post_categories pc ON c.id = pc.category_id 
          WHERE pc.post_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $post_id);
  $stmt->execute();
  $result = $stmt->get_result();
  return $result->fetch_all(MYSQLI_ASSOC);
}

function add_post_category($post_id, $category_id) {
  global $conn;
  $sql = "INSERT IGNORE INTO post_categories (post_id, category_id) VALUES (?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $post_id, $category_id);
  return $stmt->execute();
}

function remove_post_category($post_id, $category_id) {
  global $conn;
  $sql = "DELETE FROM post_categories WHERE post_id = ? AND category_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $post_id, $category_id);
  return $stmt->execute();
}

function search_posts($query) {
  global $conn;
  $search = "%{$query}%";
  $sql = "SELECT * FROM posts WHERE title LIKE ? OR content LIKE ? ORDER BY created_at DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $search, $search);
  $stmt->execute();
  $result = $stmt->get_result();
  return $result->fetch_all(MYSQLI_ASSOC);
}

function get_posts_by_category($category_id, $limit = 10) {
  global $conn;
  $sql = "SELECT p.* FROM posts p
          JOIN post_categories pc ON p.id = pc.post_id
          WHERE pc.category_id = ?
          ORDER BY p.created_at DESC LIMIT ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $category_id, $limit);
  $stmt->execute();
  $result = $stmt->get_result();
  return $result->fetch_all(MYSQLI_ASSOC);
}

function get_adjacent_post($current_id, $direction = 'next') {
  global $conn;
  
  $operator = $direction === 'next' ? '>' : '<';
  $order = $direction === 'next' ? 'ASC' : 'DESC';
  
  $sql = "SELECT id, title FROM posts WHERE id {$operator} ? ORDER BY id {$order} LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $current_id);
  $stmt->execute();
  $result = $stmt->get_result();
  return $result->fetch_assoc();
}


// User related functions
function login($username, $password) {
    global $conn;
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        return true;
    }
    return false;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function logout() {
    session_unset();
    session_destroy();
}

function get_user($id) {
    global $conn;
    $sql = "SELECT id, username, email, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function can_manage_users() {
    // Only admin users can manage other users
    return is_admin();
}

function get_all_users() {
  global $conn;
  $sql = "SELECT * FROM users ORDER BY username ASC";
  $result = $conn->query($sql);
  return $result->fetch_all(MYSQLI_ASSOC);
}

function create_user($username, $email, $password, $role) {
  global $conn;
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);
  $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
  return $stmt->execute();
}

function update_user($id, $username, $email, $role, $new_password = null) {
  global $conn;
  if ($new_password) {
      $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
      $sql = "UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssssi", $username, $email, $hashed_password, $role, $id);
  } else {
      $sql = "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sssi", $username, $email, $role, $id);
  }
  return $stmt->execute();
}

function delete_user($id) {
  global $conn;
  $sql = "DELETE FROM users WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
  return $stmt->execute();
}

function get_user_by_username($username) {
  global $conn;
  $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();
  return $result->fetch_assoc();
}


// Category related functions
function get_categories() {
  global $conn;
  $sql = "SELECT * FROM categories ORDER BY name ASC";
  $result = $conn->query($sql);
  return $result->fetch_all(MYSQLI_ASSOC);
}

function create_category($name) {
  global $conn;
  $sql = "INSERT INTO categories (name) VALUES (?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $name);
  return $stmt->execute();
}

function delete_category($id) {
  global $conn;
  // First delete all post_categories associations
  $sql = "DELETE FROM post_categories WHERE category_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
  $stmt->execute();
  
  // Then delete the category
  $sql = "DELETE FROM categories WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
  return $stmt->execute();
}

function get_category($id) {
  global $conn;
  $sql = "SELECT * FROM categories WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();
  return $result->fetch_assoc();
}


// Navigation Menu related functions
function create_nav_menu_item($label, $category_ids, $order_index) {
  global $conn;
  $conn->begin_transaction();

  try {
      $sql = "INSERT INTO nav_menu_items (label, order_index) VALUES (?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("si", $label, $order_index);
      $stmt->execute();
      
      $menu_item_id = $conn->insert_id;
      
      $sql = "INSERT INTO nav_menu_item_categories (nav_menu_item_id, category_id) VALUES (?, ?)";
      $stmt = $conn->prepare($sql);
      
      foreach ($category_ids as $category_id) {
          $stmt->bind_param("ii", $menu_item_id, $category_id);
          $stmt->execute();
      }
      
      $conn->commit();
      return true;
  } catch (Exception $e) {
      $conn->rollback();
      error_log("Error creating nav menu item: " . $e->getMessage());
      return false;
  }
}

function get_nav_menu_items() {
  global $conn;
  $sql = "SELECT nmi.id, nmi.label, nmi.order_index, 
                 GROUP_CONCAT(DISTINCT c.name ORDER BY c.name ASC SEPARATOR ', ') as category_names,
                 GROUP_CONCAT(DISTINCT c.id ORDER BY c.id ASC SEPARATOR ',') as category_ids
          FROM nav_menu_items nmi 
          LEFT JOIN nav_menu_item_categories nmic ON nmi.id = nmic.nav_menu_item_id
          LEFT JOIN categories c ON nmic.category_id = c.id 
          GROUP BY nmi.id
          ORDER BY nmi.order_index ASC";
  $result = $conn->query($sql);
  return $result->fetch_all(MYSQLI_ASSOC);
}

function delete_nav_menu_item($id) {
  global $conn;
  $conn->begin_transaction();

  try {
      $sql = "DELETE FROM nav_menu_item_categories WHERE nav_menu_item_id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i", $id);
      $stmt->execute();

      $sql = "DELETE FROM nav_menu_items WHERE id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i", $id);
      $stmt->execute();

      $conn->commit();
      return true;
  } catch (Exception $e) {
      $conn->rollback();
      return false;
  }
}

function update_nav_menu_order($order) {
  global $conn;
  $success = true;
  $conn->begin_transaction();

  try {
      $sql = "UPDATE nav_menu_items SET order_index = ? WHERE id = ?";
      $stmt = $conn->prepare($sql);

      foreach ($order as $index => $id) {
          $stmt->bind_param("ii", $index, $id);
          $stmt->execute();
      }

      $conn->commit();
  } catch (Exception $e) {
      $conn->rollback();
      $success = false;
  }

  return $success;
}


// Homepage Section related functions
function get_homepage_sections($include_inactive = false) {
    global $conn;
    
    debug_log('Starting get_homepage_sections() call');
    
    try {
        $sql = "SELECT * FROM homepage_sections";
        if (!$include_inactive) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY order_index ASC";
        
        debug_log('Executing query', $sql);
        
        $result = $conn->query($sql);
        if (!$result) {
            throw new Exception("Query failed: " . $conn->error);
        }
        
        $sections = $result->fetch_all(MYSQLI_ASSOC);
        debug_log('Retrieved sections', $sections);
        
        return $sections;
        
    } catch (Exception $e) {
        debug_log("Error getting homepage sections: " . $e->getMessage());
        return [];
    }
}

function get_section_posts($section) {
    global $conn;
    $posts = [];
    
    debug_log('Starting get_section_posts() for section', $section);

    try {
        switch ($section['section_type']) {
            case 'latest':
                $sql = "SELECT * FROM posts ORDER BY created_at DESC LIMIT ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $section['posts_count']);
                break;
                
            case 'popular':
                $sql = "SELECT * FROM posts ORDER BY view_count DESC LIMIT ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $section['posts_count']);
                break;
                
            case 'category':
                // Handle multiple categories from JSON
                $categories = json_decode($section['categories'], true);
                if (!empty($categories)) {
                    $placeholders = str_repeat('?,', count($categories) - 1) . '?';
                    $sql = "SELECT DISTINCT p.* FROM posts p
                            JOIN post_categories pc ON p.id = pc.post_id
                            WHERE pc.category_id IN ($placeholders)
                            ORDER BY p.created_at DESC LIMIT ?";
                    
                    // Prepare bind parameters
                    $types = str_repeat('i', count($categories)) . 'i';
                    $params = array_merge($categories, [$section['posts_count']]);
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types, ...$params);
                } else {
                    debug_log('No categories found for section', $section['id']);
                    return [];
                }
                break;
                
            case 'featured':
                $sql = "SELECT * FROM posts WHERE is_featured = 1 ORDER BY created_at DESC LIMIT ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $section['posts_count']);
                break;
                
            case 'random':
                $sql = "SELECT * FROM posts ORDER BY RAND() LIMIT ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $section['posts_count']);
                break;
                
            default:
                debug_log('Invalid section type', $section['section_type']);
                return [];
        }

        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
        
        debug_log('Retrieved posts for section', [
            'section_id' => $section['id'],
            'post_count' => count($posts)
        ]);
        
        return $posts;
        
    } catch (Exception $e) {
        debug_log("Error getting section posts: " . $e->getMessage());
        return [];
    }
}

function create_homepage_section($title, $section_type, $categories, $layout_type, $posts_count) {
    global $conn;
    
    try {
        error_log("Creating homepage section with params: " . print_r([
            'title' => $title,
            'section_type' => $section_type,
            'categories' => $categories,
            'layout_type' => $layout_type,
            'posts_count' => $posts_count
        ], true));

        $sql = "INSERT INTO homepage_sections (title, section_type, categories, layout_type, posts_count, is_active) 
                VALUES (?, ?, ?, ?, ?, 1)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $categories_json = json_encode($categories);
        
        $stmt->bind_param("ssssi", 
            $title,
            $section_type,
            $categories_json,
            $layout_type,
            $posts_count
        );
        
        $result = $stmt->execute();
        if (!$result) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error creating homepage section: " . $e->getMessage());
        return false;
    }
}

function update_sections_order($order) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        foreach ($order as $index => $section_id) {
            $sql = "UPDATE homepage_sections SET order_index = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $index, $section_id);
            $stmt->execute();
        }
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error updating sections order: " . $e->getMessage());
        return false;
    }
}

function toggle_section($section_id) {
    global $conn;
    
    try {
        $sql = "UPDATE homepage_sections SET is_active = NOT is_active WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $section_id);
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Error toggling section: " . $e->getMessage());
        return false;
    }
}

function delete_section($section_id) {
    global $conn;
    
    try {
        $sql = "DELETE FROM homepage_sections WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $section_id);
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Error deleting section: " . $e->getMessage());
        return false;
    }
}



function get_post_image($post) {
    // Check if post has a featured image
    if (!empty($post['featured_image'])) {
        // Ensure the path starts with a forward slash
        return '/' . ltrim($post['featured_image'], '/');
    }
    
    // Return null if no image is found
    return null;
}


// function get_post_image($post) {
//     debug_log('Getting image for post', ['post_id' => $post['id'], 'title' => $post['title']]);
    
//     if (!empty($post['featured_image'])) {
//         debug_log('Found featured image', $post['featured_image']);
//         $image_url = get_image_url($post['featured_image']);
//         debug_log('Processed featured image URL', $image_url);
//         return $image_url;
//     }
    
//     // If no featured image, try to get the first image from the content
//     preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $post['content'], $image);
//     if (isset($image['src'])) {
//         debug_log('Found image in content', $image['src']);
//         $image_url = get_image_url($image['src']);
//         debug_log('Processed content image URL', $image_url);
//         return $image_url;
//     }
    
//     debug_log('No image found for post', ['post_id' => $post['id']]);
//     return '';
// }

function get_image_url($image_path) {
    debug_log('Getting image URL for', $image_path);
    
    // Check if the image path is already a full URL
    if (filter_var($image_path, FILTER_VALIDATE_URL)) {
        debug_log('Image path is already a full URL');
        return $image_path;
    }

    // If it's a relative path, construct the full URL
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $full_url = $base_url . '/' . ltrim($image_path, '/');
    
    debug_log('Constructed full image URL', $full_url);
    return $full_url;
}

// Utility functions
function format_date($date_string) {
    return date('F j, Y g:i A', strtotime($date_string));
}

function get_excerpt($content, $length = 150) {
    $excerpt = strip_tags($content);
    if (strlen($excerpt) > $length) {
        $excerpt = substr($excerpt, 0, $length) . '...';
    }
    return $excerpt;
}

function sanitize_output($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize_output($value);
        }
    } else {
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}


function generate_slug($title) {
    // Convert to lowercase
    $slug = strtolower($title);
    // Remove spaces and special characters
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    // Remove leading and trailing hyphens
    $slug = trim($slug, '-');
    return $slug;
}


function get_unique_slug($title, $post_id = 0) {
    global $conn;
    $slug = generate_slug($title);
    $original_slug = $slug;
    $count = 1;
    
    while (true) {
        if ($post_id) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE slug = ? AND id != ?");
            $stmt->bind_param("si", $slug, $post_id);
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE slug = ?");
            $stmt->bind_param("s", $slug);
        }
        $stmt->execute();
        $stmt->bind_result($slug_count);
        $stmt->fetch();
        $stmt->close();
        
        if ($slug_count == 0) {
            return $slug;
        }
        
        $slug = $original_slug . '-' . $count;
        $count++;
    }
}


// Add or update these functions in your functions.php file

function get_posts_advanced($page = 1, $per_page = 10, $sort = 'created_at', $order = 'DESC', $category = '', $author = '', $search = '') {
    global $conn;
    
    $offset = ($page - 1) * $per_page;
    $where_clauses = array();
    $params = array();
    $types = '';
    
    // Base query
    $sql = "SELECT DISTINCT p.* FROM posts p";
    
    // Add category filter
    if (!empty($category)) {
        $sql .= " LEFT JOIN post_categories pc ON p.id = pc.post_id";
        $where_clauses[] = "pc.category_id = ?";
        $params[] = $category;
        $types .= 'i';
    }
    
    // Add author filter
    if (!empty($author)) {
        $where_clauses[] = "p.user_id = ?";
        $params[] = $author;
        $types .= 'i';
    }
    
    // Add search filter
    if (!empty($search)) {
        $search = "%$search%";
        $where_clauses[] = "(p.title LIKE ? OR p.content LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $types .= 'ss';
    }
    
    // Combine where clauses
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    // Add sorting
    $allowed_sort_columns = ['title', 'created_at'];
    $sort = in_array($sort, $allowed_sort_columns) ? $sort : 'created_at';
    $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
    
    $sql .= " ORDER BY p.$sort $order";
    
    // Add pagination
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $types .= 'ii';
    
    // Prepare and execute query
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}



function get_total_posts_count($category = '', $author = '', $search = '') {
    global $conn;
    
    $where_clauses =array();
    $params = array();
    $types = '';
    
    // Base query
    $sql = "SELECT COUNT(DISTINCT p.id) as total FROM posts p";
    
    // Add category filter
    if (!empty($category)) {
        $sql .= " LEFT JOIN post_categories pc ON p.id = pc.post_id";
        $where_clauses[] = "pc.category_id = ?";
        $params[] = $category;
        $types .= 'i';
    }
    
    // Add author filter
    if (!empty($author)) {
        $where_clauses[] = "p.user_id = ?";
        $params[] = $author;
        $types .= 'i';
    }
    
    // Add search filter
    if (!empty($search)) {
        $search = "%$search%";
        $where_clauses[] = "(p.title LIKE ? OR p.content LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $types .= 'ss';
    }
    
    // Combine where clauses
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    // Prepare and execute query
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total'];
}





function increment_post_view_count($post_id) {
    global $conn;
    
    try {
        // First check if view_count column exists
        $result = $conn->query("SHOW COLUMNS FROM posts LIKE 'view_count'");
        if ($result->num_rows == 0) {
            // Column doesn't exist, create it
            $conn->query("ALTER TABLE posts ADD COLUMN view_count INT DEFAULT 0");
        }
        
        // Now increment the view count
        $sql = "UPDATE posts SET view_count = COALESCE(view_count, 0) + 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $post_id);
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Error incrementing view count: " . $e->getMessage());
        return false; // Return false instead of throwing to prevent fatal errors
    }
}




function get_post_engagement_counts($post_id) {
    global $conn;
    
    try {
        // Check if columns exist
        $columns = ['view_count', 'like_count', 'comment_count'];
        $missing_columns = [];
        
        foreach ($columns as $column) {
            $result = $conn->query("SHOW COLUMNS FROM posts LIKE '$column'");
            if ($result->num_rows == 0) {
                $missing_columns[] = $column;
            }
        }
        
        // Add missing columns if any
        if (!empty($missing_columns)) {
            $alter_sql = "ALTER TABLE posts ";
            foreach ($missing_columns as $column) {
                $alter_sql .= "ADD COLUMN $column INT DEFAULT 0, ";
            }
            $alter_sql = rtrim($alter_sql, ", ");
            $conn->query($alter_sql);
        }
        
        // Get the counts
        $sql = "SELECT COALESCE(view_count, 0) as view_count, 
                       COALESCE(like_count, 0) as like_count, 
                       COALESCE(comment_count, 0) as comment_count 
                FROM posts WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
        
    } catch (Exception $e) {
        error_log("Error getting engagement counts: " . $e->getMessage());
        return [
            'view_count' => 0,
            'like_count' => 0,
            'comment_count' => 0
        ];
    }
}


function increment_post_like_count($post_id) {
    global $conn;
    $sql = "UPDATE posts SET like_count = like_count + 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);
    if ($stmt->execute()) {
        $sql = "SELECT like_count FROM posts WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['like_count'];
    }
    return false;
}


function get_post_views($post_id) {
  global $conn;
  $stmt = $conn->prepare("SELECT view_count FROM posts WHERE id = ?");
  $stmt->bind_param("i", $post_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  return $row['view_count'];
}

function get_post_likes($post_id) {
  global $conn;
  $stmt = $conn->prepare("SELECT like_count FROM posts WHERE id = ?");
  $stmt->bind_param("i", $post_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  return $row['like_count'];
}

function get_post_comments($post_id) {
    global $conn;
    
    try {
        // First check if comments table exists
        $table_exists = $conn->query("SHOW TABLES LIKE 'comments'");
        if ($table_exists->num_rows == 0) {
            // Table doesn't exist, create it
            $create_table_sql = "CREATE TABLE IF NOT EXISTS comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                is_approved TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
            )";
            $conn->query($create_table_sql);
        }
        
        $sql = "SELECT * FROM comments WHERE post_id = ? AND is_approved = 1 ORDER BY created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting post comments: " . $e->getMessage());
        return [];
    }
}

function add_comment($post_id, $name, $email, $content) {
    global $conn;
    
    try {
        // Start transaction
        $conn->begin_transaction();

        $sql = "INSERT INTO comments (post_id, name, email, content, is_approved) VALUES (?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("isss", $post_id, $name, $email, $content);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $comment_id = $conn->insert_id;

        // Increment comment count in posts table
        $update_sql = "UPDATE posts SET comment_count = comment_count + 1 WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        if (!$update_stmt) {
            throw new Exception("Prepare failed for update: " . $conn->error);
        }
        $update_stmt->bind_param("i", $post_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Execute failed for update: " . $update_stmt->error);
        }

        // Commit transaction
        $conn->commit();
        
        return $comment_id;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error adding comment: " . $e->getMessage());
        throw $e; // Re-throw the exception to be caught by the calling code
    }
}

function get_comment($comment_id) {
    global $conn;
    $sql = "SELECT * FROM comments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function get_post_comments_list($post_id, $limit = 10) {
  global$conn;
  $stmt = $conn->prepare("SELECT * FROM comments WHERE post_id = ? AND is_approved = 1 ORDER BY created_at DESC LIMIT ?");
  $stmt->bind_param("ii", $post_id, $limit);
  $stmt->execute();
  $result = $stmt->get_result();
  return $result->fetch_all(MYSQLI_ASSOC);
}

?>

