<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        Schema::create('games', function (Blueprint $table) {
            $table->bigIncrements('id');
            /*
             * let's have a game on one system only - other system variants may be linked through game relations as "alternative"
             */
            $table->unsignedInteger('system_id')->nullable();

            $table->string('title', 80)->nullable();

            /*
             * TODO: what are those flags for again?
             */
            // $table->unsignedSmallInteger('status_flag')->nullable();
            // $table->boolean('final')->nullable();

            // $table->string('image_icon', 50)->nullable();
            // $table->string('image_title', 50)->nullable();
            // $table->string('image_in_game', 50)->nullable();
            // $table->string('image_box_art', 50)->nullable();
            // $table->string('publisher', 50)->nullable();
            // $table->string('developer', 50)->nullable();
            // $table->string('genre', 50)->nullable();

            $table->string('release', 50)->nullable();

            /*
             * moved to triggers
             */
            // $table->text('rich_presence_patch')->nullable();
            // $table->unsignedInteger('rich_presence_patch_version')->nullable();

            /*
             * cached metrics
             */
            // $table->unsignedInteger('points_weighted')->nullable();
            // $table->unsignedInteger('points_total')->nullable();
            // $table->unsignedInteger('players_total')->nullable();
            // $table->unsignedInteger('achievements_total')->nullable();
            // $table->unsignedInteger('achievements_published')->nullable();
            // $table->unsignedInteger('achievements_unpublished')->nullable();

            /*
             * doing that through relations now...
             */
            // $table->unsignedInteger('achievements_version')->nullable();
            // $table->string('achievements_version_hash')->nullable();
            // $table->timestampTz('achievements_updated_at')->nullable();

            $table->timestampTz('released_at')->nullable();
            $table->text('releases')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('system_id');
            $table->index('title');

            $table->foreign('system_id')->references('id')->on('systems')->onDelete('set null');
        });

        /*
         * game sets may be collections of games that have a specific topic/genre
         * filtered by tags, topic, genre, series, etc etc
         * think "hubs" or "collections"
         */
        Schema::create('game_sets', function (Blueprint $table) {
            $table->bigIncrements('id');

            /*
             * somebody may or may not own this set
             */
            $table->unsignedBigInteger('user_id')->nullable();

            /*
             * might hold an eloquent query definition
             */
            $table->text('definition')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('user_id');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('game_set_games', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('game_set_id');
            $table->unsignedBigInteger('game_id');

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('game_set_id')->references('id')->on('game_sets')->onDelete('cascade');
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('game_set_games');
        Schema::dropIfExists('game_sets');
        Schema::dropIfExists('games');
    }
};
