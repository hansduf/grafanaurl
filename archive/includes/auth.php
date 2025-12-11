<?php
/**
 * auth.php
 * Authentication middleware - Check if user is logged in
 * Include this at the top of pages that require authentication
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header('Location: /login.php');
    exit;
}

// Optional: Verify user still exists in database (security check)
// This prevents using old sessions after account deletion
if (!isset($_SESSION['user_verified'])) {
    global $db;
    if (!isset($db)) {
        require_once __DIR__ . '/../config/database.php';
    }
    
    require_once __DIR__ . '/../models/UserModel.php';
    $userModel = new UserModel($db);
    $user = $userModel->getUserById($_SESSION['user_id']);
    
    if (!$user || !$user['is_active']) {
        session_destroy();
        header('Location: /login.php?error=User+tidak+aktif');
        exit;
    }
    
    $_SESSION['user_verified'] = true;
}

// Shortcut to get current user data
if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ];
    }
}

// Shortcut to check if user is admin
if (!function_exists('isCurrentUserAdmin')) {
    function isCurrentUserAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}
?>
