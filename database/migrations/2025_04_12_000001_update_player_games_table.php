<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            // These columns aren't used (except time_taken, which is only looked at to determine if
            // the player has spent at least five minutes in any game). Tather than try to change the
            // column type (which actually caused me to run out of space locally), just drop and
            // recreate them. The "at least five minutes in any game" checks are modified to check for
            // session durations instead of time_taken. The additional check can be removed after the
            // time_taken column is repopulated.
            // NOTE: rollback of this migration will not restore the data.
            $table->dropColumn([
                'playtime_total',
                'time_taken',
                'time_taken_hardcore',
            ]);
            $table->integer('playtime_total')->nullable()->default(null)->after('last_played_at');
            $table->integer('time_taken')->nullable()->default(null)->after('playtime_total');
            $table->integer('time_taken_hardcore')->nullable()->default(null)->after('time_taken');

            // new columns
            $table->integer('time_to_beat')->nullable()->default(null)->after('time_taken_hardcore');
            $table->integer('time_to_beat_hardcore')->nullable()->default(null)->after('time_to_beat');
            $table->integer('time_to_complete')->nullable()->default(null)->after('time_to_beat_hardcore');
            $table->integer('time_to_complete_hardcore')->nullable()->default(null)->after('time_to_complete');
            $table->boolean('playtime_estimated')->default(false)->after('time_to_complete_hardcore');
        });
    }

    public function down(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->dropColumn([
                'playtime_total',
                'time_taken',
                'time_taken_hardcore',
                'time_to_beat',
                'time_to_beat_hardcore',
                'time_to_complete',
                'time_to_complete_hardcore',
                'playtime_estimated',
            ]);

            $table->bigInteger('playtime_total')->nullable()->default(null)->after('last_played_at');
            $table->bigInteger('time_taken')->nullable()->default(null)->after('playtime_total');
            $table->bigInteger('time_taken_hardcore')->nullable()->default(null)->after('time_taken');
        });
    }
};
