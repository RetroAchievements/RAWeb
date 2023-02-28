<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        /*
         * the relation that makes up the active library of a user
         * deleting it means it's "hidden" -> should cascade to the user_achievement_sets
         */
        Schema::create('player_games', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('game_id');

            /*
             * anything achievement unlock related is stored on the opt-in sets
             */
            // $table->unsignedInteger('achievements_version')->nullable();
            // $table->string('achievements_version_hash')->nullable();
            // $table->unsignedInteger('achievements_total')->nullable();
            // $table->unsignedInteger('achievements_unlocked')->nullable();
            // $table->unsignedInteger('achievements_unlocked_hardcore')->nullable();
            // $table->unsignedDecimal('completion', 10, 9)->nullable();
            // $table->unsignedDecimal('completion_hardcore', 10, 9)->nullable();
            // $table->timestampTz('last_unlock_at')->nullable();
            // $table->timestampTz('last_unlock_hardcore_at')->nullable();

            /*
             * those are stored in player_sessions which is handy as it will serve as another update indicator
             * on game sessions
             */
            // $table->string('rich_presence')->nullable();
            // $table->timestampTz('rich_presence_updated_at')->nullable();
            // $table->index('rich_presence_updated_at');

            $table->timestampsTz();
            $table->softDeletesTz();

            /*
             * a game can only be once in a user's library
             */
            $table->unique(['user_id', 'game_id']);
            // $table->index(['game_id', 'achievements_version']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
        });

        Schema::create('player_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();

            /*
             * store both the game_hash and the game_hash_set here
             * while redundant, this allows to cross-check later in tickets whether that particular game hash was already
             * removed from the set since the user reported an issue
             */
            $table->unsignedBigInteger('game_hash_set_id');
            $table->unsignedBigInteger('game_hash_id');

            /*
             * game id is redundant here because of the game hash id but might be relevant as reference
             */
            $table->unsignedBigInteger('game_id');

            /*
             * add some fancy rich presence
             * remember the time when it was last updated
             * if it went stale in the meantime we would not want to display it, that'd be just odd
             */
            $table->text('rich_presence')->nullable();
            $table->timestampTz('rich_presence_updated_at')->nullable();

            /*
             * TODO: anything that is useful for in-depth cheat detection should go here
             */

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
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
            $table->foreign('game_hash_id')->references('id')->on('game_hashes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('player_sessions');
        Schema::dropIfExists('player_games');
    }
};
