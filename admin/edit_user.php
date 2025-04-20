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

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = get_user($user_id);

if (!$user) {
    header('Location: manage_users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $new_password = !empty($_POST['new_password']) ? $_POST['new_password'] : null;

    if (update_user($user_id, $username, $email, $role, $new_password)) {
        $message = "User updated successfully.";
        $message_type = "success";
        $user = get_user($user_id); // Refresh user data
    } else {
        $message = "Error updating user.";
        $message_type = "error";
    }
}

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Edit user form -->
        <!-- ... (Form for editing user details) ... -->
    </div>
</body>
</html>

<?php include '../includes/footer.php'; ?>

