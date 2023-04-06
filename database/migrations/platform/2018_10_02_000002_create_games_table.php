<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('GameData', function (Blueprint $table) {
            $table->bigIncrements('ID')->change();
            $table->unsignedInteger('ConsoleID')->nullable()->change();

            $table->string('Title', 80)->nullable()->change();

            /*
             * metrics (all sets)
             * should match achievement_sets
             */
            $table->unsignedInteger('players_total')->nullable()->after('RichPresencePatch');
            $table->unsignedInteger('achievements_published')->nullable()->after('players_total');
            $table->unsignedInteger('achievements_unpublished')->nullable()->after('achievements_published');
            $table->unsignedInteger('points_total')->nullable()->after('achievements_unpublished');

            $table->timestampTz('released_at')->nullable()->after('Released');
            $table->text('releases')->nullable()->after('released_at');
            $table->softDeletesTz();

            // Alphabetic sort
            $table->index('title', 'games_title_index');
            $table->index('released_at', 'games_released_at_index');

            $table->foreign('ConsoleID', 'games_systems_id_foreign')->references('ID')->on('Console')->onDelete('set null');
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

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });

        Schema::create('game_set_games', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('game_set_id');
            $table->unsignedBigInteger('game_id');

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('game_set_id')->references('id')->on('game_sets')->onDelete('cascade');
            $table->foreign('game_id')->references('ID')->on('GameData')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_set_games');
        Schema::dropIfExists('game_sets');

        Schema::table('GameData', function (Blueprint $table) {
            $table->dropIndex('games_title_index');
            $table->dropForeign('games_systems_id_foreign');
            $table->dropColumn('players_total');
            $table->dropColumn('achievements_published');
            $table->dropColumn('achievements_unpublished');
            $table->dropColumn('points_total');
            $table->dropColumn('released_at');
            $table->dropColumn('releases');
            $table->dropSoftDeletesTz();
        });
    }
};
