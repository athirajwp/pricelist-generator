<?php

// Debug script to check the admin login issue
// Run via: artisan.bat tinker --execute="require 'backend/scratch/debug_admin_login.php';"

echo "=== DEBUG: Admin Login Diagnostics ===" . PHP_EOL;

// 1. Check central DB connectivity
try {
    $companies = DB::connection('central')->select("SELECT code, name, website, status FROM companies ORDER BY id");
    echo "Central DB OK - " . count($companies) . " companies found:" . PHP_EOL;
    foreach ($companies as $c) {
        echo "  [{$c->code}] {$c->name} | website: {$c->website} | status: {$c->status}" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Central DB ERROR: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// 2. Check if tenant DBs exist
$codes = ['crackersdemo1', 'crackersdemo2', 'crackersdemo3'];
foreach ($codes as $code) {
    $tenantDb = 'crackers_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $code));
    try {
        $r = DB::connection('central')->select("SELECT 1 FROM pg_database WHERE datname = ?", [$tenantDb]);
        $exists = !empty($r);
        echo "Tenant DB [{$tenantDb}]: " . ($exists ? "EXISTS" : "NOT FOUND") . PHP_EOL;
        
        if ($exists) {
            // Switch to tenant DB and check tables
            $cfg = config('database.connections.pgsql');
            $cfg['database'] = $tenantDb;
            config(["database.connections.tenant_check" => $cfg]);
            DB::purge('tenant_check');
            
            try {
                $tables = DB::connection('tenant_check')->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
                $tableNames = array_column($tables, 'tablename');
                echo "  Tables: " . implode(', ', $tableNames) . PHP_EOL;
                
                // Check settings table for admin_password
                if (in_array('settings', $tableNames)) {
                    $adminPwd = DB::connection('tenant_check')->select("SELECT value FROM settings WHERE key = 'admin_password' LIMIT 1");
                    echo "  admin_password setting: " . (empty($adminPwd) ? "NOT SET (will use env default)" : "IS SET") . PHP_EOL;
                } else {
                    echo "  WARNING: 'settings' table NOT found!" . PHP_EOL;
                }
                
                // Check migrations
                if (in_array('migrations', $tableNames)) {
                    $migrations = DB::connection('tenant_check')->select("SELECT migration FROM migrations ORDER BY id");
                    echo "  Migrations run: " . count($migrations) . PHP_EOL;
                }
            } catch (Exception $innerEx) {
                echo "  Error reading tenant DB: " . $innerEx->getMessage() . PHP_EOL;
            }
        }
    } catch (Exception $e) {
        echo "Error checking [{$tenantDb}]: " . $e->getMessage() . PHP_EOL;
    }
    echo PHP_EOL;
}

// 3. Check database config
echo "=== Database Config ===" . PHP_EOL;
$conn = config('database.default');
echo "Default connection: {$conn}" . PHP_EOL;

$centralCfg = config('database.connections.central');
echo "Central DB: {$centralCfg['host']}:{$centralCfg['port']}/{$centralCfg['database']}" . PHP_EOL;

echo PHP_EOL . "=== Done ===" . PHP_EOL;
