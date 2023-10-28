<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->string('achievement_set_version_hash')->nullable()->after('game_hash_id');
            $table->string('update_status')->nullable()->after('achievement_set_version_hash');

            $table->unsignedDecimal('completion_percentage', 10, 9)->nullable()
                ->change();

            $table->unsignedInteger('achievements_beat')->nullable()->after('achievements_unlocked_hardcore');
            $table->unsignedInteger('achievements_beat_unlocked')->nullable()->after('achievements_beat');
            $table->unsignedInteger('achievements_beat_unlocked_hardcore')->nullable()->after('achievements_beat_unlocked');

            $table->unsignedDecimal('beaten_percentage', 10, 9)->nullable()->after('achievements_beat_unlocked_hardcore');
            $table->unsignedDecimal('beaten_percentage_hardcore', 10, 9)->nullable()->after('beaten_percentage');

            $table->jsonb('beaten_dates')->nullable()->after('time_taken_hardcore');
            $table->jsonb('beaten_dates_hardcore')->nullable()->after('beaten_dates');

            $table->timestampTz('beaten_at')->nullable()->after('completion_dates_hardcore');
            $table->timestampTz('beaten_hardcore_at')->nullable()->after('beaten_at');

            $table->unsignedInteger('points_hardcore')->nullable()->after('points');
        });

        Schema::table('player_achievement_sets', function (Blueprint $table) {
            $table->unsignedDecimal('completion_percentage', 10, 9)->nullable()
                ->change();

            $table->unsignedInteger('achievements_beat')->nullable()->after('achievements_unlocked_hardcore');
            $table->unsignedInteger('achievements_beat_unlocked')->nullable()->after('achievements_beat');
            $table->unsignedInteger('achievements_beat_unlocked_hardcore')->nullable()->after('achievements_beat_unlocked');

            $table->unsignedDecimal('beaten_percentage', 10, 9)->nullable()->after('achievements_beat_unlocked_hardcore');
            $table->unsignedDecimal('beaten_percentage_hardcore', 10, 9)->nullable()->after('beaten_percentage');

            $table->jsonb('beaten_dates')->nullable()->after('time_taken_hardcore');
            $table->jsonb('beaten_dates_hardcore')->nullable()->after('beaten_dates');

            $table->timestampTz('beaten_at')->nullable()->after('completion_dates_hardcore');
            $table->timestampTz('beaten_hardcore_at')->nullable()->after('beaten_at');

            $table->unsignedInteger('points_hardcore')->nullable()->after('points');
        });
    }

    public function down(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->dropColumn('achievement_set_version_hash');
            $table->dropColumn('update_status');
            $table->dropColumn('achievements_beat');
            $table->dropColumn('achievements_beat_unlocked');
            $table->dropColumn('achievements_beat_unlocked_hardcore');
            $table->dropColumn('beaten_percentage');
            $table->dropColumn('beaten_percentage_hardcore');
            $table->dropColumn('beaten_dates');
            $table->dropColumn('beaten_dates_hardcore');
            $table->dropColumn('beaten_at');
            $table->dropColumn('beaten_hardcore_at');
            $table->dropColumn('points_hardcore');
        });

        Schema::table('player_achievement_sets', function (Blueprint $table) {
            $table->dropColumn('achievements_beat');
            $table->dropColumn('achievements_beat_unlocked');
            $table->dropColumn('achievements_beat_unlocked_hardcore');
            $table->dropColumn('beaten_percentage');
            $table->dropColumn('beaten_percentage_hardcore');
            $table->dropColumn('beaten_dates');
            $table->dropColumn('beaten_dates_hardcore');
            $table->dropColumn('beaten_at');
            $table->dropColumn('beaten_hardcore_at');
            $table->dropColumn('points_hardcore');
        });
    }
};
