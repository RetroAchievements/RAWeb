<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        /*
         * users can opt into multiple sets of a game
         * community sets are served with unofficial achievements (TODO: or with official as well?)
         */
        Schema::create('player_achievement_sets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');

            $table->unsignedBigInteger('achievement_set_id');

            /*
             * no reference to version here -> always playing the latest version of the set
             * if other achievements should be in there for development -> additional set opt-in
             */
            // $table->unsignedInteger('achievement_set_version')->nullable();

            /*
             * Metrics - should match player_games table
             */
            $table->unsignedInteger('achievements_total')->nullable();
            $table->unsignedInteger('achievements_unlocked')->nullable();
            $table->unsignedInteger('achievements_unlocked_hardcore')->nullable();
            $table->unsignedDecimal('completion_percentage', 20, 16)->nullable();
            $table->unsignedDecimal('completion_percentage_hardcore', 10, 9)->nullable();
            $table->timestampTz('last_played_at')->nullable();
            $table->unsignedBigInteger('playtime_total')->nullable();
            $table->unsignedBigInteger('time_taken')->nullable(); // first_lock until last_unlock
            $table->unsignedBigInteger('time_taken_hardcore')->nullable(); // first_unlock_hardcore until last_unlock_hardcore
            $table->jsonb('completion_dates')->nullable();
            $table->jsonb('completion_dates_hardcore')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('completed_hardcore_at')->nullable();
            $table->timestampTz('last_unlock_at')->nullable(); // any, hardcore or softcore
            $table->timestampTz('last_unlock_hardcore_at')->nullable();
            $table->timestampTz('first_unlock_at')->nullable(); // any, hardcore or softcore
            $table->timestampTz('first_unlock_hardcore_at')->nullable();
            $table->unsignedInteger('points_total')->nullable();
            $table->unsignedInteger('points')->nullable();
            $table->unsignedInteger('points_weighted_total')->nullable();
            $table->unsignedInteger('points_weighted')->nullable();

            $table->timestampsTz();
            /*
             * let's have those deleted for good
             * we don't have to keep everything
             * note: removing the official core set should probably be prevented
             */
            // $table->softDeletesTz();

            /*
             * users should only sign into a set once
             */
            $table->unique(['user_id', 'achievement_set_id']);

            // $table->index(['achievement_set_id', 'achievement_set_version'], 'user_achievement_sets_id_version_index');

            $table->foreign('achievement_set_id')->references('id')->on('achievement_sets')->onDelete('cascade');
            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_achievement_sets');
    }
};
