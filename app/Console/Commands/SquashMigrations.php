<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Safely squash all migrations into schema dumps.
 *
 * The command will:
 * 1. Generate a MySQL schema dump including all migration records.
 * 2. Create a temporary SQLite database, run all migrations, then generate
 *    a SQLite schema dump with migration records.
 * 3. Remove all migration files (except those in the 'upcoming' folder).
 */
class SquashMigrations extends Command
{
    protected $signature = 'ra:db:squash-migrations';
    protected $description = 'Safely squash all migrations into schema dumps';

    public function handle(): void
    {
        if (!$this->confirm('This will squash all migrations and update schema dumps. Continue?')) {
            $this->info('Operation cancelled.');

            return;
        }

        $this->info('🚀 Starting migration squash...');
        $this->newLine();

        try {
            // Step 1: Generate MySQL schema dump.
            $this->info('Generating MySQL schema dump...');
            Artisan::call('schema:dump', ['--database' => 'mysql']);
            $this->info('MySQL schema dump generated');

            // Step 2: Create temporary SQLite database and generate schema.
            $this->info('Generating SQLite schema dump...');
            $this->generateSqliteSchema();
            $this->info('SQLite schema dump generated');

            // Step 3: Delete migration files (keep upcoming folder).
            $this->info('Removing squashed migration files...');
            $this->deleteMigrationFiles();
            $this->info('Migration files removed');

            $this->info('Migration squash completed successfully!');
            $this->newLine();

            $this->info('Summary:');
            $this->info('- MySQL schema: database/schema/mysql-schema.sql');
            $this->info('- SQLite schema: database/schema/sqlite-schema.sql');
            $this->info('- Migration files: removed (except upcoming folder)');

        } catch (Exception $e) {
            $this->error('❌ Migration squash failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function generateSqliteSchema(): void
    {
        $tempDb = database_path('temp_squash.sqlite');

        try {
            // Create the temp SQLite database.
            File::put($tempDb, '');

            // Back up the original SQLite configuration.
            $originalConfig = Config::get('database.connections.sqlite');

            // Set the SQLite configuration to use our temp database.
            Config::set('database.connections.sqlite.database', $tempDb);

            // Purge the SQLite connection to force reconnection with the new config.
            DB::purge('sqlite');

            // Run fresh migrations on SQLite.
            Artisan::call('migrate:fresh', [
                '--database' => 'sqlite',
                '--force' => true,
            ]);

            // Generate the SQLite schema dump.
            Artisan::call('schema:dump', [
                '--database' => 'sqlite',
            ]);

            // Fix sqlite_sequence issue so tests don't implode.
            $sqliteSchemaPath = database_path('schema/sqlite-schema.sql');
            $schemaContent = File::get($sqliteSchemaPath);
            $fixedContent = str_replace("CREATE TABLE sqlite_sequence(name,seq);\n", '', $schemaContent);
            File::put($sqliteSchemaPath, $fixedContent);

            // Restore the original SQLite configuration.
            Config::set('database.connections.sqlite', $originalConfig);
            DB::purge('sqlite');
        } finally {
            // Clean up the temp database.
            if (File::exists($tempDb)) {
                File::delete($tempDb);
            }
        }
    }

    private function deleteMigrationFiles(): void
    {
        $patterns = [
            'database/migrations/[0-9][0-9][0-9][0-9]_*.php',
        ];

        foreach ($patterns as $pattern) {
            $files = glob(base_path($pattern));
            foreach ($files as $file) {
                if (File::exists($file)) {
                    File::delete($file);
                }
            }
        }
    }
}
