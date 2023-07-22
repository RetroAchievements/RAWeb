<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class() extends Migration {
    public function up(): void
    {
        $this->migrateCharsetTo('utf8mb4', 'utf8mb4_unicode_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // do not revert
        // $this->migrateCharsetTo('latin1', 'latin1_general_ci');
    }

    protected function migrateCharsetTo(string $charset, string $collation): void
    {
        $defaultConnection = config('database.default');
        $databaseName = config("database.connections.{$defaultConnection}.database");

        DB::unprepared("ALTER SCHEMA `{$databaseName}` DEFAULT CHARACTER SET {$charset} DEFAULT COLLATE {$collation};");

        $tableNames = DB::table('information_schema.tables')
            ->where('table_schema', $databaseName)
            ->pluck('TABLE_NAME');

        $ignoredTables = [
            'Activity',
            'Awarded',
        ];

        foreach ($tableNames as $tableName) {
            if (in_array($tableName, $ignoredTables)) {
                continue;
            }
            echo PHP_EOL . '   Converting ' . $tableName;
            DB::unprepared("ALTER TABLE {$tableName} CONVERT TO CHARACTER SET {$charset} COLLATE {$collation};");
        }
    }
};
