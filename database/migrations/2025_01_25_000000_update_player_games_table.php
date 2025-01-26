<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->dropIndex('player_games_completion_sample_idx');

            $table->index([
                'user_id',
                'achievements_unlocked',
                'achievements_total',
                'game_id',
            ], 'player_games_suggestions_index'); // custom name needed because the auto-generated one is too long
        });
    }

    public function down(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->dropIndex('player_games_suggestions_index');

            $table->index(
                [
                    'game_id',
                    'achievements_unlocked',
                    'achievements_total',
                    'user_id',
                    'id',
                ],
                'player_games_completion_sample_idx' // custom name needed because the auto-generated one is too long
            );
        });
    }
};
