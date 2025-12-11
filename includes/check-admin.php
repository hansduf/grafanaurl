<?php
/**
 * check-admin.php
 * Admin-only middleware - Check if user is admin
 * Include this at the top of pages that require admin access
 * Must be included AFTER auth.php
 */

// Make sure auth.php is already included
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    die('Access Denied: Admin privileges required');
}
?>
