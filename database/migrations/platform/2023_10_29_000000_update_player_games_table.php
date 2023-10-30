<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->index(['game_id', 'achievements_unlocked']);
            $table->index(['game_id', 'achievements_unlocked_hardcore']);
        });
    }

    public function down(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->dropIndex(['game_id', 'achievements_unlocked']);
            $table->dropIndex(['game_id', 'achievements_unlocked_hardcore']);
        });
    }
};
