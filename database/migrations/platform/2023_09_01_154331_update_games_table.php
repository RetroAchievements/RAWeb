<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumns('GameData', ['players_hardcore'])) {
            Schema::table('GameData', function (Blueprint $table) {
                $table->unsignedInteger('players_hardcore')->nullable()->after('players_total');

                $table->index('players_total', 'games_players_total_index');
                $table->index('players_hardcore', 'games_players_hardcore_index');
            });
        }
        if (!Schema::hasColumns('GameData', ['achievement_set_version_hash'])) {
            Schema::table('GameData', function (Blueprint $table) {
                $table->string('achievement_set_version_hash')->nullable()->after('players_hardcore');
            });
        }

        if (!Schema::hasColumns('achievement_sets', ['players_hardcore'])) {
            Schema::table('achievement_sets', function (Blueprint $table) {
                $table->unsignedInteger('players_hardcore')->nullable()->after('players_total');

                $table->index('players_total');
                $table->index('players_hardcore');
            });
        }

        if (!Schema::hasColumns('achievement_set_versions', ['players_hardcore'])) {
            Schema::table('achievement_set_versions', function (Blueprint $table) {
                $table->unsignedInteger('players_hardcore')->nullable()->after('players_total');

                $table->index('players_total');
                $table->index('players_hardcore');
            });
        }
    }

    public function down(): void
    {
        Schema::table('GameData', function (Blueprint $table) {
            $table->dropColumn('players_hardcore');
        });

        Schema::table('achievement_sets', function (Blueprint $table) {
            $table->dropColumn('players_hardcore');
        });

        Schema::table('achievement_set_versions', function (Blueprint $table) {
            $table->dropColumn('players_hardcore');
        });
    }
};
