<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->index(['game_id', 'achievements_unlocked'], 'game_id_achievements_unlocked_index');
            $table->index(['game_id', 'achievements_unlocked_hardcore'], 'game_id_achievements_unlocked_hardcore_index');
        });
    }

    public function down(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->dropIndex('game_id_achievements_unlocked_index');
            $table->dropIndex('game_id_achievements_unlocked_hardcore_index');
        });
    }
};
