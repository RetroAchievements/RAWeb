<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('Achievements', function (Blueprint $table) {
            $table->bigIncrements('id')->change();

            /*
             * direct reference to game
             * achievement are additionally assigned to games via sets - redundant but necessary
             */
            $table->unsignedBigInteger('GameID')->nullable()->change();

            /*
             * title and description are audited via activity log for reference
             * note: badges stages can have their own title and description
             * achievements override those
             *
             * Flags was used to determine whether an achievement is in core or not
             * this is not relevant anymore as that is done via achievement sets
             * the publish date is the updated_at date within a set
             *
             * achievement badges are assigned via badge sets on games
             *
             * game hashes are assigned to triggers via game hash sets on games
             */

            /*
             * the owner of this achievement
             * somebody created it after all by writing a concept for it
             */
            $table->unsignedBigInteger('user_id')->nullable()->after('Author');

            /**
             * metrics
             */
            $table->unsignedInteger('unlocks_total')->nullable()->after('user_id');
            $table->unsignedInteger('unlocks_hardcore_total')->nullable()->after('unlocks_total');
            $table->decimal('unlock_percentage', 10, 9)->nullable()->after('unlocks_hardcore_total');
            $table->decimal('unlock_hardcore_percentage', 10, 9)->nullable()->after('unlock_percentage');

            $table->softDeletesTz();

            $table->foreign('GameID', 'achievements_game_id_foreign')->references('ID')->on('GameData')->onDelete('cascade');
            $table->foreign('user_id', 'achievements_user_id_foreign')->references('ID')->on('UserAccounts')->onDelete('set null');
        });

        /*
         * credit authors for achievement/badge idea, their triggers etc
         */
        Schema::create('achievement_authors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('achievement_id');
            $table->unsignedBigInteger('user_id');
            $table->string('task')->nullable(); // default: create, review, revise, test, ...

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['achievement_id', 'user_id']);

            $table->foreign('achievement_id')->references('ID')->on('Achievements')->onDelete('cascade');
            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
        });

        /*
         * achievements may be in multiple sets
         */
        Schema::create('achievement_sets', function (Blueprint $table) {
            $table->bigIncrements('id');

            /*
             * somebody may or may not own this set
             */
            $table->unsignedBigInteger('user_id')->nullable();

            /*
             * metrics (all latest versions)
             * should match achievement_set_versions
             */
            $table->unsignedInteger('players_total')->nullable();
            $table->unsignedInteger('achievements_published')->nullable();
            $table->unsignedInteger('achievements_unpublished')->nullable();
            $table->unsignedInteger('points_total')->nullable();
            $table->unsignedInteger('points_weighted')->nullable();

            /*
             * the set can be discussed
             * any given forum topic id on a synced game should be to an achievement set instead
             * not: using forumable morph on forum topics
             */
            // $table->unsignedBigInteger('forum_topic_id')->nullable();

            /*
             * achievements sets have an active badge set assigned which can be swapped e.g. for changing assets
             * Update: NO. games have badge_sets, individual achievements refer to a particular stage of a badge
             */
            // $table->unsignedBigInteger('active_badge_set_id')->nullable();

            /*
             * created_at -> added to the set initially
             * updated_at -> added to the set, touch each time it's added back
             * deleted_at -> removed from the set
             */
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('user_id');

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });

        Schema::create('achievement_set_achievements', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('achievement_set_id');
            $table->unsignedBigInteger('achievement_id');

            $table->unsignedInteger('order_column')->nullable();

            $table->timestampsTz();

            $table->unique(['achievement_set_id', 'achievement_id'], 'achievement_set_achievement_unique');

            $table->foreign('achievement_set_id')->references('id')->on('achievement_sets')->onDelete('cascade');
            $table->foreign('achievement_id')->references('ID')->on('Achievements')->onDelete('cascade');
        });

        /*
         * store references to various versions of the set
         * this should be used for official sets - not for community sets (for now at least)
         */
        Schema::create('achievement_set_versions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('achievement_set_id');
            $table->unsignedInteger('version')->nullable();

            /*
             * json including achievement ids and corresponding version
             */
            $table->mediumText('definition')->nullable();

            /**
             * Metrics should match achievements_sets
             */
            $table->unsignedInteger('players_total')->nullable();
            $table->unsignedInteger('achievements_published')->nullable();
            $table->unsignedInteger('achievements_unpublished')->nullable();
            $table->unsignedInteger('points_total')->nullable();
            $table->unsignedInteger('points_weighted')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['achievement_set_id', 'version']);

            $table->foreign('achievement_set_id')->references('id')->on('achievement_sets')->onDelete('cascade');
        });

        /*
         * credit authors for set compositions
         */
        Schema::create('achievement_set_authors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('achievement_set_id');
            $table->unsignedBigInteger('user_id');
            $table->string('task')->nullable(); // default: create, review, revise, test, ...

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('achievement_set_id')->references('id')->on('achievement_sets')->onDelete('cascade');
            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievement_set_authors');
        Schema::dropIfExists('achievement_set_versions');
        Schema::dropIfExists('achievement_set_achievements');
        Schema::dropIfExists('achievement_sets');
        Schema::dropIfExists('achievement_authors');

        Schema::table('Achievements', function (Blueprint $table) {
            $table->dropForeign('achievements_game_id_foreign');
            $table->dropForeign('achievements_user_id_foreign');
            $table->dropColumn('user_id');
            $table->dropColumn('unlocks_total');
            $table->dropColumn('unlocks_hardcore_total');
            $table->dropColumn('unlock_percentage');
            $table->dropColumn('unlock_hardcore_percentage');
            $table->dropSoftDeletesTz();
        });
    }
};
