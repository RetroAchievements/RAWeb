<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // SQLite doesn't support dropping foreign keys by name.
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        // 1. Drop all FK constraints.
        if (!$isSqlite) {
            Schema::table('GameData', fn (Blueprint $t) => $t->dropForeign('games_systems_id_foreign'));
            Schema::table('game_hashes', fn (Blueprint $t) => $t->dropForeign('game_hashes_system_id_foreign'));
            Schema::table('player_stats', fn (Blueprint $t) => $t->dropForeign('player_stats_system_id_foreign'));
            Schema::table('system_emulators', fn (Blueprint $t) => $t->dropForeign('system_emulators_system_id_foreign'));
            Schema::table('player_stat_rankings', fn (Blueprint $t) => $t->dropForeign('player_stat_rankings_system_id_foreign'));
        }

        // 2. Rename the table.
        Schema::rename('Console', 'systems');

        // 3. Rename the table columns.
        Schema::table('systems', function (Blueprint $table) {
            $table->renameColumn('ID', 'id');
            $table->renameColumn('Name', 'name');
            $table->renameColumn('Created', 'created_at');
            $table->renameColumn('Updated', 'updated_at');
        });

        // 4. Recreate the FK constraints.
        if (!$isSqlite) {
            Schema::table('GameData', fn (Blueprint $t) => $t->foreign('ConsoleID', 'games_systems_id_foreign')
                ->references('id')->on('systems')->onDelete('set null'));
            Schema::table('game_hashes', fn (Blueprint $t) => $t->foreign('system_id', 'game_hashes_system_id_foreign')
                ->references('id')->on('systems')->onDelete('cascade'));
            Schema::table('player_stats', fn (Blueprint $t) => $t->foreign('system_id', 'player_stats_system_id_foreign')
                ->references('id')->on('systems')->onDelete('cascade'));
            Schema::table('system_emulators', fn (Blueprint $t) => $t->foreign('system_id', 'system_emulators_system_id_foreign')
                ->references('id')->on('systems')->onDelete('cascade'));
            Schema::table('player_stat_rankings', fn (Blueprint $t) => $t->foreign('system_id', 'player_stat_rankings_system_id_foreign')
                ->references('id')->on('systems')->onDelete('cascade'));
        }
    }

    public function down(): void
    {
        // 1. Drop all FK constraints.
        Schema::table('GameData', fn (Blueprint $t) => $t->dropForeign('games_systems_id_foreign'));
        Schema::table('game_hashes', fn (Blueprint $t) => $t->dropForeign('game_hashes_system_id_foreign'));
        Schema::table('player_stats', fn (Blueprint $t) => $t->dropForeign('player_stats_system_id_foreign'));
        Schema::table('system_emulators', fn (Blueprint $t) => $t->dropForeign('system_emulators_system_id_foreign'));
        if (Schema::hasTable('player_stat_rankings')) {
            Schema::table('player_stat_rankings', fn (Blueprint $t) => $t->dropForeign('player_stat_rankings_system_id_foreign'));
        }

        // 2. Rename the table columns.
        Schema::table('systems', function (Blueprint $table) {
            $table->renameColumn('id', 'ID');
            $table->renameColumn('name', 'Name');
            $table->renameColumn('created_at', 'Created');
            $table->renameColumn('updated_at', 'Updated');
        });

        // 3. Rename the table.
        Schema::rename('systems', 'Console');

        // 4. Recreate the FK constraints.
        Schema::table('GameData', fn (Blueprint $t) => $t->foreign('ConsoleID', 'games_systems_id_foreign')
            ->references('ID')->on('Console')->onDelete('set null'));
        Schema::table('game_hashes', fn (Blueprint $t) => $t->foreign('system_id', 'game_hashes_system_id_foreign')
            ->references('ID')->on('Console')->onDelete('cascade'));
        Schema::table('player_stats', fn (Blueprint $t) => $t->foreign('system_id', 'player_stats_system_id_foreign')
            ->references('ID')->on('Console')->onDelete('cascade'));
        Schema::table('system_emulators', fn (Blueprint $t) => $t->foreign('system_id', 'system_emulators_system_id_foreign')
            ->references('ID')->on('Console')->onDelete('cascade'));
        Schema::table('player_stat_rankings', fn (Blueprint $t) => $t->foreign('system_id', 'player_stat_rankings_system_id_foreign')
            ->references('ID')->on('Console')->onDelete('cascade'));
    }
};
