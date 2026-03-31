<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // The column doesn't seem to exist in the SQLite schema.
        if (Schema::hasColumn('messages', 'Unread')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropColumn('Unread');
            });
        }
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('Unread')->default(true)->after('created_at');
        });
    }
};
