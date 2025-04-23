<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->dropColumn([
                'achievement_set_version_hash',
                'achievements_beat',
                'achievements_beat_unlocked',
                'achievements_beat_unlocked_hardcore',
                'beaten_percentage',
                'beaten_percentage_hardcore',
                'playtime_total',
                'time_taken',
                'time_taken_hardcore',
                'first_unlock_hardcore_at',
            ]);

            $table->integer('playtime_total')->nullable()->default(null)->after('last_played_at');
            $table->integer('time_to_beat')->nullable()->default(null)->after('playtime_total');
            $table->integer('time_to_beat_hardcore')->nullable()->default(null)->after('time_to_beat');
        });
    }

    public function down(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->dropColumn([
                'playtime_total',
                'time_to_beat',
                'time_to_beat_hardcore',
            ]);

            $table->string('achievement_set_version_hash', 255)->nullable()->default(null)->after('game_hash_id');
            $table->integer('achievements_beat')->nullable()->default(null)->after('achievements_unlocked_softcore');
            $table->integer('achievements_beat_unlocked')->nullable()->default(null)->after('achievements_beat');
            $table->integer('achievements_beat_unlocked_hardcore')->nullable()->default(null)->after('achievements_beat_unlocked');
            $table->decimal('beaten_percentage', 10, 9)->nullable()->default(null)->after('achievements_beat_unlocked_hardcore');
            $table->decimal('beaten_percentage_hardcore', 10, 9)->nullable()->default(null)->after('beaten_percentage');
            $table->bigInteger('playtime_total')->nullable()->default(null)->after('last_played_at');
            $table->bigInteger('time_taken')->nullable()->default(null)->after('playtime_total');
            $table->bigInteger('time_taken_hardcore')->nullable()->default(null)->after('time_taken');
            $table->dateTime('first_unlock_hardcore_at')->nullable()->default(null)->after('first_unlock_at');
            $table->dateTime('last_unlock_hardcore_at')->nullable()->default(null)->after('last_unlock_at');
        });
    }
};
