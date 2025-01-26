<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->index([
                'user_id',
                'achievements_unlocked',
                'achievements_total',
                'game_id',
            ], 'idx_player_games_suggestions'); // custom name needed because the auto-generated one is too long
        });
    }

    public function down(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->dropIndex('idx_player_games_suggestions');
        });
    }
};
