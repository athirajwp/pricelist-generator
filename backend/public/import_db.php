<?php
// Automatic Hostinger Database Importer Script
try {
    $envPath = __DIR__ . '/.env';
    if (!file_exists($envPath)) {
        $envPath = __DIR__ . '/../.env';
    }
    
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $config = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $config[trim($name)] = trim($value, " '\"");
        }
    }

    $host = $config['DB_HOST'] ?? 'localhost';
    $dbname = $config['DB_DATABASE'] ?? '';
    $user = $config['DB_USERNAME'] ?? '';
    $pass = $config['DB_PASSWORD'] ?? '';

    if (empty($dbname) || empty($user)) {
        die("Database credentials in .env are empty! Please check DB_DATABASE and DB_USERNAME in .env");
    }

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $sqlPath = __DIR__ . '/database_dump.sql';
    if (!file_exists($sqlPath)) {
        $sqlPath = __DIR__ . '/../database_dump.sql';
    }

    if (!file_exists($sqlPath)) {
        die("database_dump.sql file not found!");
    }

    $sql = file_get_contents($sqlPath);
    $pdo->exec($sql);

    echo "<h1 style='color: green; font-family: sans-serif;'>✅ Database Imported Successfully!</h1>";
    echo "<p style='font-family: sans-serif;'>All tables and data imported into <strong>" . htmlspecialchars($dbname) . "</strong>.</p>";
    echo "<p style='font-family: sans-serif;'><a href='/' style='background: #16a34a; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Website Home</a></p>";

} catch (Exception $e) {
    echo "<h1 style='color: red; font-family: sans-serif;'>❌ Import Failed</h1>";
    echo "<pre style='background: #fee2e2; padding: 15px; border-radius: 5px; color: #991b1b;'>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
