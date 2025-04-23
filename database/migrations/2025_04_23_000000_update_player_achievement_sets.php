<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_achievement_sets', function (Blueprint $table) {
            $table->dropColumn([
                'achievements_total',
                'achievements_beat',
                'achievements_beat_unlocked',
                'achievements_beat_unlocked_hardcore',
                'beaten_percentage',
                'beaten_percentage_hardcore',
                'last_played_at',
                'playtime_total',
                'time_taken',
                'time_taken_hardcore',
                'beaten_dates',
                'beaten_dates_hardcore',
                'beaten_at',
                'beaten_hardcore_at',
                'completed_at',
                'completed_hardcore_at',
                'first_unlock_at',
                'first_unlock_hardcore_at',
                'points_total',
                'points_weighted_total',
            ]);

            $table->integer('time_taken')->nullable()->default(null)->after('completion_percentage_hardcore');
            $table->integer('time_taken_hardcore')->nullable()->default(null)->after('time_taken');
            $table->integer('time_to_complete')->nullable()->default(null)->after('time_taken_hardcore');
            $table->integer('time_to_complete_hardcore')->nullable()->default(null)->after('time_to_complete');
        });
    }

    public function down(): void
    {
        Schema::table('player_achievement_sets', function (Blueprint $table) {
            $table->dropColumn([
                'time_to_complete',
                'time_to_complete_hardcore',
                'time_taken',
                'time_taken_hardcore',
            ]);

            $table->integer('achievements_total')->nullable()->default(null)->after('achievement_set_id');
            $table->integer('achievements_beat')->nullable()->default(null)->after('achievements_unlocked_hardcore');
            $table->integer('achievements_beat_unlocked')->nullable()->default(null)->after('achievements_beat');
            $table->integer('achievements_beat_unlocked_hardcore')->nullable()->default(null)->after('achievements_beat_unlocked');
            $table->decimal('beaten_percentage', 10, 9)->nullable()->default(null)->after('achievements_beat_unlocked_hardcore');
            $table->decimal('beaten_percentage_hardcore', 10, 9)->nullable()->default(null)->after('beaten_percentage');
            $table->dateTime('last_played_at')->nullable()->default(null)->after('completion_percentage_hardcore');
            $table->bigInteger('playtime_total')->nullable()->default(null)->after('last_played_at');
            $table->bigInteger('time_taken')->nullable()->default(null)->after('playtime_total');
            $table->bigInteger('time_taken_hardcore')->nullable()->default(null)->after('time_taken');
            $table->longText('beaten_dates')->nullable()->default(null)->after('time_taken_hardcore');
            $table->longText('beaten_dates_hardcore')->nullable()->default(null)->after('beaten_dates');
            $table->dateTime('beaten_at')->nullable()->default(null)->after('completion_dates_hardcore');
            $table->dateTime('beaten_hardcore_at')->nullable()->default(null)->after('beaten_at');
            $table->dateTime('completed_at')->nullable()->default(null)->after('beaten_hardcore_at');
            $table->dateTime('completed_hardcore_at')->nullable()->default(null)->after('completed_at');
            $table->dateTime('first_unlock_at')->nullable()->default(null)->after('last_unlock_hardcore_at');
            $table->dateTime('first_unlock_hardcore_at')->nullable()->default(null)->after('first_unlock_at');
            $table->integer('points_total')->nullable()->default(null)->after('first_unlock_hardcore_at');
            $table->integer('points_weighted_total')->nullable()->default(null)->after('points_hardcore');
        });
    }
};
