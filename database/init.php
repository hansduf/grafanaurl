<?php
/**
 * Database Initialization Helper
 * Run this script once to set up the MySQL database schema
 * Access via: http://localhost:8000/database/init.php
 */

require_once __DIR__ . '/../config.php';

try {
    // Load .env
    $host = getenv('MYSQL_HOST') ?: 'localhost';
    $port = getenv('MYSQL_PORT') ?: 3306;
    $database = getenv('MYSQL_DATABASE') ?: 'grafana';
    $username = getenv('MYSQL_USERNAME') ?: 'root';
    $password = getenv('MYSQL_PASSWORD') ?: '';

    // Connect to MySQL
    $connection = new mysqli($host, $username, $password, $database, $port);
    
    if ($connection->connect_error) {
        die('Connection Error: ' . $connection->connect_error);
    }

    // Set charset
    $connection->set_charset('utf8mb4');

    // Read schema file
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        die('Error: schema.sql not found at ' . $schemaFile);
    }

    $schema = file_get_contents($schemaFile);

    // Split schema into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', trim($stmt));
        }
    );

    // Execute each statement
    $count = 0;
    foreach ($statements as $statement) {
        if (trim($statement)) {
            if (!$connection->query($statement)) {
                throw new Exception('Execute error: ' . $connection->error);
            }
            $count++;
        }
    }

    $connection->close();
    
    echo '<h2 style="color: green;">✓ Database initialized successfully!</h2>';
    echo '<p>Executed ' . $count . ' SQL statements</p>';
    echo '<ul>';
    echo '<li>Tables: channels, media</li>';
    echo '<li>Database: ' . htmlspecialchars($database) . '</li>';
    echo '<li>Host: ' . htmlspecialchars($host) . ':' . $port . '</li>';
    echo '</ul>';
    
} catch (Exception $e) {
    echo '<h2 style="color: red;">✗ Database initialization failed!</h2>';
    echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<ul>';
    echo '<li>Check .env configuration</li>';
    echo '<li>Ensure MySQL server is running</li>';
    echo '<li>Verify database credentials</li>';
    echo '</ul>';
}
?>
