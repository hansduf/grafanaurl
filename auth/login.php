<?php
/**
 * auth/login.php
 * Login page - Username & password authentication
 */

session_start();

// If already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

// Load environment
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

require_once __DIR__ . '/../models/UserModel.php';

// Initialize MySQL connection
$host = getenv('MYSQL_HOST') ?: 'localhost';
$port = getenv('MYSQL_PORT') ?: 3306;
$database = getenv('MYSQL_DATABASE') ?: 'grafana';
$username = getenv('MYSQL_USERNAME') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: '';

$db = new mysqli($host, $username, $password, $database, $port);

if ($db->connect_error) {
    die('Database connection error: ' . $db->connect_error);
}

$db->set_charset('utf8mb4');

$userModel = new UserModel($db);
$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = $userModel->verifyLogin($username, $password);

    if ($result['success']) {
        // Set session variables
        $_SESSION['user_id'] = $result['user']['id'];
        $_SESSION['username'] = $result['user']['username'];
        $_SESSION['role'] = $result['user']['role'];
        $_SESSION['user_verified'] = true;

        // Redirect to home
        header('Location: /');
        exit;
    } else {
        $error_message = $result['message'];
    }
}

// Get error/success messages from URL params
$error_message = $_GET['error'] ?? $error_message;
$success_message = $_GET['success'] ?? $success_message;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login - Media Console</title>
    <link href="/src/output.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="w-full max-w-md">
            <!-- Card -->
            <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-gradient-to-br from-sky-500 to-sky-600 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Media Console</h1>
                    <p class="text-sm text-gray-600">Channel Management & Monitoring</p>
                </div>

                <!-- Alerts -->
                <?php if ($error_message): ?>
                    <div class="mb-6 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="mb-6 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form method="POST" action="" class="space-y-4">
                    <input type="hidden" name="action" value="login">

                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="Masukkan username"
                            autocomplete="username"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-colors"
                        >
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-colors"
                        >
                    </div>

                    <button type="submit" class="w-full py-2 px-4 bg-gradient-to-r from-sky-500 to-sky-600 text-white font-semibold rounded-lg hover:shadow-lg hover:from-sky-600 hover:to-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-all duration-200 shadow-md mt-6">
                        Login
                    </button>
                </form>

                <!-- Footer -->
                <div class="mt-6 pt-6 border-t border-gray-200 text-center">
                    <p class="text-xs text-gray-600">Hubungi admin untuk membuat akun baru</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
