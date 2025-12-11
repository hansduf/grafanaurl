<?php
session_start();

// If not authenticated, redirect to login
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// Else redirect to main page
header('Location: /views/index.php', true, 302);
exit;
