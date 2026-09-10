<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DbImportDumpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:import-dump {file? : Path to the .sql dump file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import SQL dump file into the database (supports MySQL and SQLite)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (!$filePath) {
            $candidates = [
                base_path('u405695954_pricelist(1).sql'),
                base_path('database_dump.sql'),
                database_path('u405695954_pricelist.sql'),
                base_path('../database_dump.sql'),
            ];

            foreach ($candidates as $cand) {
                if (File::exists($cand)) {
                    $filePath = $cand;
                    break;
                }
            }
        }

        if (!$filePath || !File::exists($filePath)) {
            $this->error("SQL dump file not found!");
            return 1;
        }

        $this->info("Importing SQL dump from: " . $filePath);

        $driver = DB::connection()->getDriverName();
        $this->info("Database Connection Driver: " . $driver);

        $sqlContent = File::get($filePath);

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::unprepared($sqlContent);
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } else {
            // For SQLite or non-MySQL drivers, parse INSERT statements
            $statements = $this->extractInsertStatements($sqlContent);

            if ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = OFF;');
            }

            $count = 0;
            foreach ($statements as $stmt) {
                preg_match('/INSERT\s+INTO\s+`?([a-zA-Z0-9_]+)`?/i', $stmt, $m);
                $tableName = $m[1] ?? 'unknown';

                // Skip migrations table to prevent conflict with Laravel's migration runner
                if ($tableName === 'migrations') {
                    continue;
                }

                try {
                    $cleanStmt = str_replace("\'", "''", $stmt);
                    DB::unprepared($cleanStmt);
                    $count++;
                } catch (\Exception $e) {
                    $this->warn("Insert warning for {$tableName}: " . substr($e->getMessage(), 0, 150));
                }
            }

            if ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            }

            $this->info("Executed {$count} insert blocks successfully.");
        }

        $this->displaySummary();

        $this->info("✅ Database import completed successfully!");
        return 0;
    }

    private function extractInsertStatements(string $sql): array
    {
        $lines = explode("\n", $sql);
        $statements = [];
        $current = '';
        $inInsert = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (!$inInsert) {
                if (preg_match('/^INSERT\s+INTO\s+/i', $trimmed)) {
                    $inInsert = true;
                    $current = $line . "\n";
                    if (str_ends_with($trimmed, ';')) {
                        $statements[] = $current;
                        $current = '';
                        $inInsert = false;
                    }
                }
            } else {
                $current .= $line . "\n";
                if (str_ends_with($trimmed, ';')) {
                    $statements[] = $current;
                    $current = '';
                    $inInsert = false;
                }
            }
        }

        return $statements;
    }

    private function displaySummary(): void
    {
        $tables = ['categories', 'products', 'companies', 'settings', 'orders', 'order_items'];
        $summary = [];

        foreach ($tables as $table) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                    $count = DB::table($table)->count();
                    $summary[] = [$table, $count];
                }
            } catch (\Exception $e) {}
        }

        $this->table(['Table', 'Total Rows'], $summary);
    }
}
