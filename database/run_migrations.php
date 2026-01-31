<?php
/**
 * Run Database Migrations
 * This script scans the database/migrations directory and applies pending SQL migrations.
 */

// Load database connection
require_once dirname(__DIR__) . '/config/db_connect.php';

// CSS for better UI
echo "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Migrations - St. George Hospital</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; color: #333; margin: 40px; }
        .container { max-width: 800px; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin: 0 auto; }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .success { color: #27ae60; background: #eafaf1; padding: 10px; border-radius: 5px; margin: 10px 0; border-left: 5px solid #27ae60; }
        .error { color: #c0392b; background: #fdf2f2; padding: 10px; border-radius: 5px; margin: 10px 0; border-left: 5px solid #c0392b; }
        .info { color: #2980b9; background: #ebf5fb; padding: 10px; border-radius: 5px; margin: 10px 0; border-left: 5px solid #2980b9; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 13px; }
        .btn { display: inline-block; background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .btn:hover { background: #2980b9; }
    </style>
</head>
<body>
<div class='container'>
    <h1>Database Migrations</h1>
";

$migrationsDir = __DIR__ . '/migrations';

if (!is_dir($migrationsDir)) {
    echo "<div class='error'>Migration directory not found: <code>$migrationsDir</code></div>";
    echo "</div></body></html>";
    exit;
}

// Get all SQL files
$files = glob($migrationsDir . '/*.sql');
sort($files); // Run them in alphabetical order

if (empty($files)) {
    echo "<div class='info'>No migration files found in <code>$migrationsDir</code>.</div>";
} else {
    foreach ($files as $file) {
        $filename = basename($file);
        echo "<h3>Processing: <code>$filename</code></h3>";
        
        try {
            $sql = file_get_contents($file);
            
            // Execute the SQL
            // Note: PDO exec() or query() might not support multiple statements in one call depending on settings.
            // But these migration files seem to have simple ALTER statements.
            // For safety, we can split by semicolon if needed, but let's try direct execution first.
            
            $pdo->exec($sql);
            
            echo "<div class='success'>Successfully applied migration: <strong>$filename</strong></div>";
        } catch (PDOException $e) {
            echo "<div class='error'>Failed to apply migration: <strong>$filename</strong><br>";
            echo "Error: " . h($e->getMessage()) . "</div>";
        }
    }
}

echo "
    <hr>
    <div class='info'>Migrated completed. You can now use the updated features.</div>
    <a href='../auth/login.php' class='btn'>Go to Login Page</a>
</div>
</body>
</html>
";
?>
