<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    public function up(): void
    {
        // Full-text indexes are not supported by SQLite.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('UserAccounts', function (Blueprint $table) {
                $table->fullText(['User', 'display_name']);
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('UserAccounts', function (Blueprint $table) {
                $table->dropFullText(['User', 'display_name']);
            });
        }
    }
};
