<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add a virtual generated column for efficient sorting by "most recent unlock"
        // without needing COALESCE in queries. This enables index usage for ORDER BY.
        Schema::table('player_achievements', function (Blueprint $table) {
            $table->timestamp('unlocked_effective_at')
                ->virtualAs('COALESCE(unlocked_hardcore_at, unlocked_at)')
                ->nullable()
                ->after('unlocked_hardcore_at');
        });

        // Add an index for achievement "recent unlockers" queries.
        // The composite index allows efficient filtering by achievement_id
        // and sorting by the virtual column.
        Schema::table('player_achievements', function (Blueprint $table) {
            $table->index(
                ['achievement_id', 'unlocked_effective_at'],
                'player_achievements_achievement_id_unlocked_effective_at_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('player_achievements', function (Blueprint $table) {
            $table->dropIndex('player_achievements_achievement_id_unlocked_effective_at_index');
            $table->dropColumn('unlocked_effective_at');
        });
    }
};
