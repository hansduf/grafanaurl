<?php
/**
 * setup-admin.php
 * Script untuk membuat admin user pertama
 * Run dari CLI: php setup-admin.php
 */

// Load environment
if (!function_exists('loadEnv')) {
    function loadEnv($filePath = __DIR__ . '/.env') {
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
            
            // Remove quotes
            if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1)) {
                $value = substr($value, 1, -1);
            }
            
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

loadEnv();

require_once __DIR__ . '/models/UserModel.php';

// Initialize MySQL connection
$host = getenv('MYSQL_HOST') ?: 'localhost';
$port = getenv('MYSQL_PORT') ?: 3306;
$database = getenv('MYSQL_DATABASE') ?: 'grafana';
$username = getenv('MYSQL_USERNAME') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: '';

$db = new mysqli($host, $username, $password, $database, $port);

if ($db->connect_error) {
    echo "❌ Database connection error: " . $db->connect_error . "\n";
    exit(1);
}

$db->set_charset('utf8mb4');

$userModel = new UserModel($db);

echo "================================\n";
echo "🔧 Grafana Admin Setup\n";
echo "================================\n\n";

// Check if admin already exists
$existingAdmin = $userModel->getUserByUsername('admin');
if ($existingAdmin) {
    echo "⚠️  Admin user sudah ada!\n";
    echo "Username: admin\n";
    echo "User ID: " . $existingAdmin['id'] . "\n";
    echo "\nJika ingin reset password, edit di User Management page.\n";
    exit;
}

// Create admin user
$result = $userModel->createUser('admin', 'admin123', 'admin');

if ($result['success']) {
    echo "✅ Admin user berhasil dibuat!\n\n";
    echo "📋 Login Credentials:\n";
    echo "   Username: admin\n";
    echo "   Password: admin123\n";
    echo "   User ID: " . $result['user_id'] . "\n";
    echo "   Role: Admin\n\n";
    echo "⚠️  PENTING: Ganti password setelah login pertama!\n";
} else {
    echo "❌ Error: " . $result['message'] . "\n";
    exit(1);
}
?>
