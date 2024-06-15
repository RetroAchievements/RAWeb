<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->unsignedInteger('achievements_unlocked_softcore')
                ->after('achievements_unlocked_hardcore')
                ->nullable();
            $table->index(['game_id', 'achievements_unlocked_softcore']);
        });
    }

    public function down(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->dropIndex(['game_id', 'achievements_unlocked_softcore']);
            $table->dropColumn('achievements_unlocked_softcore');
        });
    }
};
