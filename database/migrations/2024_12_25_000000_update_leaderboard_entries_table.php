<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('leaderboard_entries', function (Blueprint $table) {
            $table->index(
                ['deleted_at', 'updated_at', 'leaderboard_id'],
                'idx_recent_entries'
            );
        });
    }

    public function down(): void
    {
        Schema::table('leaderboard_entries', function (Blueprint $table) {
            $table->dropIndex('idx_recent_entries');
        });
    }
};
