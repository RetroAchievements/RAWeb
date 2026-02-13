<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->dropColumn('time_taken');
            $table->dropColumn('all_achievements_total');
            $table->dropColumn('all_achievements_unlocked');
            $table->dropColumn('all_achievements_unlocked_hardcore');
            $table->dropColumn('all_points_total');
            $table->dropColumn('all_points');
            $table->dropColumn('all_points_hardcore');
            $table->dropColumn('all_points_weighted');
        });
    }

    public function down(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->integer('all_achievements_total')->nullable()->default(null)->after('achievements_unlocked_softcore');
            $table->integer('all_achievements_unlocked')->nullable()->default(null)->after('all_achievements_total');
            $table->integer('all_achievements_unlocked_hardcore')->nullable()->default(null)->after('all_achievements_unlocked');
            $table->integer('all_points_total')->nullable()->default(null)->after('all_achievements_unlocked_hardcore');
            $table->integer('all_points')->nullable()->default(null)->after('all_points_total');
            $table->integer('all_points_hardcore')->nullable()->default(null)->after('all_points');
            $table->integer('all_points_weighted')->nullable()->default(null)->after('all_points_hardcore');

            $table->bigInteger('time_taken')->nullable()->default(null)->after('time_to_beat_hardcore');
        });
    }
};
