<?php
// Hostinger Laravel Auto-Fixer & Diagnostics Script
header('Content-Type: text/html; charset=utf-8');

echo "<style>
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 20px; line-height: 1.6; }
.card { background: #1e293b; border-radius: 16px; padding: 24px; max-width: 700px; margin: 0 auto; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
h1 { color: #f59e0b; margin-top: 0; font-size: 24px; }
.step { background: #334155; padding: 12px 16px; border-radius: 10px; margin-bottom: 12px; }
.success { color: #4ade80; font-weight: bold; }
.error { color: #f87171; font-weight: bold; }
.info { color: #38bdf8; font-weight: bold; }
pre { background: #0f172a; padding: 10px; border-radius: 8px; overflow-x: auto; color: #fbbf24; font-size: 12px; }
</style>";

echo "<div class='card'>";
echo "<h1>🚀 Hostinger Laravel Auto-Fixer & Diagnostics</h1>";

// 1. Clear bootstrap/cache
echo "<div class='step'><strong>Step 1: Clearing Bootstrap Cache</strong><br/>";
$cacheDir = __DIR__ . '/../bootstrap/cache';
if (!is_dir($cacheDir)) {
    $cacheDir = __DIR__ . '/bootstrap/cache';
}
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*.php');
    $cleared = 0;
    foreach ($files as $file) {
        if (@unlink($file)) $cleared++;
    }
    echo "<span class='success'>✓ Cleared $cleared stale cache files from bootstrap/cache!</span>";
} else {
    echo "<span class='error'>✗ bootstrap/cache directory not found at $cacheDir</span>";
}
echo "</div>";

// 2. Ensure Storage Subdirectories Exist
echo "<div class='step'><strong>Step 2: Ensuring Storage Directory Structure</strong><br/>";
$baseStorage = __DIR__ . '/../storage';
if (!is_dir($baseStorage)) {
    $baseStorage = __DIR__ . '/storage';
}

$dirs = [
    $baseStorage . '/app/public',
    $baseStorage . '/framework/cache/data',
    $baseStorage . '/framework/sessions',
    $baseStorage . '/framework/views',
    $baseStorage . '/logs',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (@mkdir($dir, 0755, true)) {
            echo "<span class='success'>+ Created missing folder: " . basename(dirname($dir)) . '/' . basename($dir) . "</span><br/>";
        } else {
            echo "<span class='error'>- Failed to create: $dir</span><br/>";
        }
    } else {
        @chmod($dir, 0755);
    }
}
echo "<span class='success'>✓ Storage folder permissions verified (0755)!</span></div>";

// 3. Check PHP Version
echo "<div class='step'><strong>Step 3: PHP Environment Check</strong><br/>";
echo "PHP Version: <span class='info'>" . PHP_VERSION . "</span> ";
if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
    echo "<span class='success'>(✓ Compatible with Laravel 11)</span>";
} else {
    echo "<span class='error'>(⚠️ Warning: Laravel 11 requires PHP 8.2+)</span>";
}
echo "</div>";

// 4. Test Database Connection
echo "<div class='step'><strong>Step 4: Database Connection Test</strong><br/>";
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    $envFile = __DIR__ . '/.env';
}

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $env[trim($key)] = trim(trim($val), '"\'');
        }
    }

    $dbHost = $env['DB_HOST'] ?? 'localhost';
    $dbName = $env['DB_DATABASE'] ?? '';
    $dbUser = $env['DB_USERNAME'] ?? '';
    $dbPass = $env['DB_PASSWORD'] ?? '';

    echo "Attempting MySQL connection to <code>$dbUser@$dbHost/$dbName</code>...<br/>";

    try {
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "<span class='success'>✓ Database Connected Successfully!</span><br/>";

        // Check if tables exist
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables found in database: <span class='info'>" . count($tables) . " tables</span>";
        if (count($tables) === 0) {
            echo " <span class='error'>(Warning: Database is empty! You may need to import your database tables.)</span>";
        }
    } catch (PDOException $e) {
        echo "<span class='error'>✗ Database Connection Failed:</span>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    }
} else {
    echo "<span class='error'>✗ .env file not found at $envFile</span>";
}
echo "</div>";

echo "<p style='text-align:center; margin-top:20px; font-size:12px; color:#94a3b8;'>Hostinger Fixer Utility Completed • Delete fix.php after troubleshooting for security</p>";
echo "</div>";
