<?php
/**
 * auth/logout.php
 * Logout endpoint - Destroy session dan redirect to login
 */

session_start();

// Destroy session
session_destroy();

// Redirect to login with success message
header('Location: /auth/login.php?success=Logout+berhasil');
exit;
?>
