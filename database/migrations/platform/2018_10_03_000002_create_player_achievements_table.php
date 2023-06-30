<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('player_achievements')) {
            return;
        }

        // sync target for Awarded
        Schema::create('player_achievements', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id');

            $table->unsignedBigInteger('achievement_id');

            /*
             * the user unlocks an achievement at a specific version
             * this allows to easily relock achievements of a certain state
             *
             * we cannot know at which version a user unlocked the achievement for synced unlocks
             *
             * the achievement version may be a dev version as well
             * whether it counts towards completion, points etc is based on the version itself and whether the achievement is in an official set
             */
            $table->unsignedBigInteger('trigger_id')->nullable();

            /*
             * let's keep a reference to the game session here to know more about the unlock
             * the game session includes both the game_hash_set_id and the identifier hash that the user used
             */
            $table->unsignedBigInteger('player_session_id')->nullable();

            /*
             * usually it's the user who unlocks the achievement, yet it might have been a user who did so
             * we cannot know who unlocked the achievement for synced unlocks
             *
             * manual unlocks should not be done for achievement versions that are not official
             */
            $table->unsignedBigInteger('unlocker_id')->nullable();

            /*
             * unlocked in any way - be it official or a dev version
             * whether it was unlocked from within an official set is determined by the version
             */
            $table->timestampTz('unlocked_at')->nullable();

            /*
             * treat hardcore unlocks the same regardless of being an official/unofficial version
             */
            $table->timestampTz('unlocked_hardcore_at')->nullable();

            /*
             * soft delete for reference -> unlock might be removed because the version got invalidated etc
             */

            /*
             * an achievement should only trigger once by a user
             */
            $table->unique(['user_id', 'achievement_id']);

            $table->index('unlocked_at');

            $table->foreign('achievement_id')->references('ID')->on('Achievements')->onDelete('cascade');
            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
            $table->foreign('trigger_id')->references('id')->on('triggers')->onDelete('set null');
            $table->foreign('player_session_id')->references('id')->on('player_sessions')->onDelete('set null');
            $table->foreign('unlocker_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_achievements');
    }
};
