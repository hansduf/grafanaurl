<?php
/**
 * QUICK START GUIDE - PostgreSQL Database Setup
 * 
 * ====== STEP 1: Configure Database ======
 * Open config.php and update these values:
 *   - DB_HOST: 'localhost' or your Supabase host
 *   - DB_PORT: 5432
 *   - DB_NAME: your database name
 *   - DB_USER: 'postgres' or your username
 *   - DB_PASSWORD: your password
 * 
 * ====== STEP 2: Initialize Database ======
 * Option A (Web Interface - Recommended):
 *   1. Start PostgreSQL server
 *   2. Visit: http://localhost/grafana/database/init.php
 *   3. You should see "Database Initialized Successfully!"
 * 
 * Option B (Manual SQL):
 *   1. Open your PostgreSQL client
 *   2. Execute: database/schema.sql
 *   3. Verify tables: channels, channel_logs, settings
 * 
 * ====== STEP 3: Test Connection ======
 */

// Test database connection
$config = include __DIR__ . '/../config.php';

try {
    $dsn = sprintf(
        'pgsql:host=%s;port=%d;dbname=%s',
        $config['DB_HOST'],
        $config['DB_PORT'],
        $config['DB_NAME']
    );

    $pdo = new PDO(
        $dsn,
        $config['DB_USER'],
        $config['DB_PASSWORD']
    );

    // Test query
    $result = $pdo->query('SELECT COUNT(*) as count FROM channels')->fetch();
    
    echo '<h1 style="color: green;">✓ Database Connection Successful!</h1>';
    echo '<p>Connected to: ' . $config['DB_HOST'] . '/' . $config['DB_NAME'] . '</p>';
    echo '<p>Channels in database: ' . $result['count'] . '</p>';
    
    // Check if tables exist
    $tables = $pdo->query(
        "SELECT table_name FROM information_schema.tables 
         WHERE table_schema = 'public' 
         ORDER BY table_name"
    )->fetchAll(PDO::FETCH_COLUMN);
    
    echo '<h2>Database Tables:</h2>';
    echo '<ul>';
    foreach ($tables as $table) {
        echo '<li>' . htmlspecialchars($table) . '</li>';
    }
    echo '</ul>';
    
    echo '<h2>API Test:</h2>';
    echo '<p><a href="/api.php?endpoint=channels" target="_blank">Get all channels → /api.php?endpoint=channels</a></p>';
    
} catch (PDOException $e) {
    echo '<h1 style="color: red;">✗ Database Connection Failed</h1>';
    echo '<p style="color: red; font-weight: bold;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    
    echo '<p>Your current config.php settings:</p>';
    echo '<pre>';
    echo 'DB_HOST: ' . $config['DB_HOST'] . "\n";
    echo 'DB_PORT: ' . $config['DB_PORT'] . "\n";
    echo 'DB_NAME: ' . $config['DB_NAME'] . "\n";
    echo 'DB_USER: ' . $config['DB_USER'] . "\n";
    echo 'DB_PASSWORD: [' . (strlen($config['DB_PASSWORD']) ? 'SET' : 'NOT SET') . ']';
    echo '</pre>';
    
    echo '<h3>Troubleshooting:</h3>';
    echo '<ul>';
    echo '<li>Is PostgreSQL server running?</li>';
    echo '<li>Are credentials in config.php correct?</li>';
    echo '<li>Does the database exist?</li>';
    echo '<li>Does the user have permission to connect?</li>';
    echo '</ul>';
    
    echo '<p><a href="/database/init.php">Try initialization script →</a></p>';
}
?>
