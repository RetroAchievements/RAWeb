<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('player_games')) {
            return;
        }

        /*
         * the relation that makes up the active library of a user
         * deleting it means it's "hidden" -> should cascade to the user_achievement_sets
         */
        Schema::create('player_games', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('game_id');

            // redundant and singular latest game hash id reference
            // can be dropped as soon as player sessions are intact which allow to track multiple hashes over time
            $table->unsignedBigInteger('game_hash_id')->nullable();

            /*
             * Metrics - should match player_achievement_sets table
             */
            $table->unsignedInteger('achievements_total')->nullable();
            $table->unsignedInteger('achievements_unlocked')->nullable();
            $table->unsignedInteger('achievements_unlocked_hardcore')->nullable();
            $table->unsignedDecimal('completion_percentage', 20, 16)->nullable(); // calculated completion (unlocked_hardcore * 2 + unlocked_casual-unlocked_hardcore) / achievements_total * 2
            $table->unsignedDecimal('completion_percentage_hardcore', 10, 9)->nullable();
            $table->timestampTz('last_played_at')->nullable();
            $table->unsignedBigInteger('playtime_total')->nullable();
            $table->unsignedBigInteger('time_taken')->nullable(); // first_lock until last_unlock
            $table->unsignedBigInteger('time_taken_hardcore')->nullable(); // first_unlock_hardcore until last_unlock_hardcore
            $table->jsonb('completion_dates')->nullable();
            $table->jsonb('completion_dates_hardcore')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('completed_hardcore_at')->nullable();
            $table->timestampTz('last_unlock_at')->nullable(); // any, hardcore or casual
            $table->timestampTz('last_unlock_hardcore_at')->nullable();
            $table->timestampTz('first_unlock_at')->nullable(); // any, hardcore or casual
            $table->timestampTz('first_unlock_hardcore_at')->nullable();
            $table->unsignedInteger('points_total')->nullable();
            $table->unsignedInteger('points')->nullable();
            $table->unsignedInteger('points_weighted_total')->nullable();
            $table->unsignedInteger('points_weighted')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            /*
             * a game can only be once in a user's library
             */
            $table->unique(['user_id', 'game_id']);

            $table->index('created_at');

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
            $table->foreign('game_id')->references('ID')->on('GameData')->onDelete('cascade');
            $table->foreign('game_hash_id')->references('id')->on('GameHashLibrary')->onDelete('set null');
        });

        Schema::create('player_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();

            /*
             * store both the game_hash and the game_hash_set here
             * while redundant, this allows to cross-check later in tickets whether that particular game hash was already
             * removed from the set since the user reported an issue
             */
            $table->unsignedBigInteger('game_hash_set_id')->nullable();
            $table->unsignedBigInteger('game_hash_id')->nullable();

            /*
             * game id is redundant here because of the game hash id but might be relevant as reference
             */
            $table->unsignedBigInteger('game_id')->nullable();

            $table->boolean('hardcore')->nullable();

            $table->text('rich_presence')->nullable();
            $table->timestampTz('rich_presence_updated_at')->nullable();

            /*
             * derived from start & end date
             */
            $table->unsignedInteger('duration');

            /*
             * created -> started
             * updated -> ended
             */
            $table->timestampsTz();

            /*
             * do not remove when a player is deleted -> historic game session info might still be relevant for tickets
             */
            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('set null');
            $table->foreign('game_id')->references('ID')->on('GameData')->onDelete('set null');
            $table->foreign('game_hash_set_id')->references('id')->on('game_hash_sets')->onDelete('set null');
            $table->foreign('game_hash_id')->references('id')->on('GameHashLibrary')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_sessions');
        Schema::dropIfExists('player_games');
    }
};
