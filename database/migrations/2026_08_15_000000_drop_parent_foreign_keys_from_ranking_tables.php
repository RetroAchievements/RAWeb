<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * player_global_rankings and player_stat_rankings are materialized views
     * that are fully rebuilt on write.
     *
     * InnoDB locks each referenced parent row during FK checks. It holds these
     * locks until the rebuild actually commits. This blocks writes to the parent
     * table. Transaction isolation does not change these locks, so we're removing
     * the constraint altogether.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('player_global_rankings', function (Blueprint $table) {
            $table->dropForeign('player_global_rankings_user_id_foreign');
            $table->dropIndex('player_global_rankings_user_id_foreign');
        });

        Schema::table('player_stat_rankings', function (Blueprint $table) {
            $table->dropForeign('player_stat_rankings_user_id_foreign');

            $table->dropForeign('player_stat_rankings_last_game_id_foreign');
            $table->dropIndex('player_stat_rankings_last_game_id_foreign');
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('player_global_rankings', function (Blueprint $table) {
            $table->foreign('user_id', 'player_global_rankings_user_id_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::table('player_stat_rankings', function (Blueprint $table) {
            $table->foreign('user_id', 'player_stat_rankings_user_id_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('last_game_id', 'player_stat_rankings_last_game_id_foreign')
                ->references('id')
                ->on('games')
                ->nullOnDelete();
        });
    }
};
