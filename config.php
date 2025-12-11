<?php
// Load environment variables from .env file
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

// Auto-detect protocol and host from .env or defaults
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$port = getenv('APP_PORT');
$host = $_SERVER['HTTP_HOST'] ?? ($port ? 'localhost:' . $port : 'localhost');

return [
    'BASE_URL' => $protocol . '://' . $host,
    'UPLOAD_DIR' => __DIR__ . '/' . (getenv('UPLOAD_DIR') ?: 'uploads'),
    'MAX_FILE_SIZE' => (int)(getenv('MAX_FILE_SIZE') ?: 104857600),
    'ALLOWED_MIME' => array_map('trim', explode(',', getenv('ALLOWED_MIME') ?: 'image/png,image/jpeg,image/gif,video/mp4,video/webm,audio/mpeg,audio/ogg')),
];

