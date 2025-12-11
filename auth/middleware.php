<?php
/**
 * auth/middleware.php
 * Authentication middleware - Check if user is logged in
 * Include this at the top of pages that require authentication
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header('Location: /auth/login.php');
    exit;
}

// Optional: Verify user still exists in database (security check)
// This prevents using old sessions after account deletion
if (!isset($_SESSION['user_verified'])) {
    // Load environment if needed
    if (!function_exists('loadEnv')) {
        function loadEnv($filePath = __DIR__ . '/../.env') {
            if (!file_exists($filePath)) {
                throw new Exception('.env file not found at: ' . $filePath);
            }
            
            $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1)) {
                    $value = substr($value, 1, -1);
                }
                
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }

    loadEnv();

    // Initialize MySQL connection if not exists
    if (!isset($db)) {
        $host = getenv('MYSQL_HOST') ?: 'localhost';
        $port = getenv('MYSQL_PORT') ?: 3306;
        $database = getenv('MYSQL_DATABASE') ?: 'grafana';
        $username = getenv('MYSQL_USERNAME') ?: 'root';
        $password = getenv('MYSQL_PASSWORD') ?: '';

        $db = new mysqli($host, $username, $password, $database, $port);

        if ($db->connect_error) {
            header('Location: /auth/login.php?error=Database+connection+error');
            exit;
        }

        $db->set_charset('utf8mb4');
    }
    
    require_once __DIR__ . '/../models/UserModel.php';
    $userModel = new UserModel($db);
    $user = $userModel->getUserById($_SESSION['user_id']);
    
    if (!$user || !$user['is_active']) {
        session_destroy();
        header('Location: /auth/login.php?error=User+tidak+aktif');
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
