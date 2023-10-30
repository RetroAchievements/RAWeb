<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_achievements', function (Blueprint $table) {
            $table->index(['user_id', 'unlocked_at', 'unlocked_hardcore_at', 'achievement_id']);
        });
    }

    public function down(): void
    {
        Schema::table('player_achievements', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'unlocked_at', 'unlocked_hardcore_at', 'achievement_id']);
        });
    }
};
