<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->bigIncrements('id');

            /*
             * direct reference to game
             * achievement are additionally assigned to games via sets - redundant but necessary
             */
            $table->unsignedBigInteger('game_id')->nullable()->index();
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');

            /*
             * moved to polymorphic triggers table
             */
            // $table->unsignedBigInteger('trigger_id');

            /*
             * the owner of this achievement
             * somebody created it after all by writing a concept for it
             */
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            /*
             * title and description are audited via activity log for reference
             * note: badges stages can have their own title and description
             * achievements override those
             */
            $table->string('title')->nullable();
            $table->string('description')->nullable();

            $table->unsignedInteger('points')->nullable();
            // $table->unsignedInteger('points_weighted')->nullable();

            /*
             * status flag was used to determine whether an achievement is in core or not
             * this is not relevant anymore as that is done via achievement sets
             * the publish date is the updated_at date within a set
             */
            // $table->tinyInteger('status_flag')->nullable();

            /*
             * achievement badges are assigned via badge sets on games
             */
            // $table->string('badge_name')->nullable();

            /*
             * game hashes are assigned to triggers via game hash sets on games
             */
            // $table->unsignedBigInteger('game_hash_id')->nullable();
            // $table->foreign('game_hash_id')->references('id')->on('game_hashes')->onDelete('set null');

            $table->timestampsTz();
            $table->softDeletesTz();

            /*
             * votes have been moved to polymorphic votes table
             */
            // $table->unsignedInteger('votes_positive')->nullable();
            // $table->unsignedInteger('votes_negative')->nullable();

            /*
             * TODO: what to do about videos? -> should be moved to guides or forum even? something that is better suited for metadata
             */
            // $table->string('video')->nullable();

            /*
             * let's keep cashed values out of here for now
             * TODO: check back in a while
             */
            // $table->unsignedInteger('unlocks_total')->nullable();
            // $table->unsignedInteger('unlocks_hardcore_total')->nullable();
            // $table->decimal('unlock_percentage', 10, 9)->nullable();
            // $table->decimal('unlock_hardcore_percentage', 10, 9)->nullable();

            $table->index('points');
            // $table->index('points_weighted');
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

            $table->foreign('achievement_id')->references('id')->on('achievements')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
             * the set can be discussed
             * any given forum topic id on a synced game should be to an achievement set instead
             * not: using forumable morph on forum topics
             */
            // $table->unsignedBigInteger('forum_topic_id')->nullable();

            /*
             * achievements sets have an active badge set assigned which can be swapped e.g. for changing assets
             * Update: NO. games have bage_sets, individual achievements refer to a particular stage of a badge
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

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('achievement_set_achievements', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('achievement_set_id');
            $table->unsignedBigInteger('achievement_id');

            $table->unsignedInteger('order_column')->nullable();

            $table->timestampsTz();
            // $table->softDeletesTz(); // TODO: soft deletes?

            $table->unique(['achievement_set_id', 'achievement_id'], 'achievement_set_achievement_unique');

            $table->foreign('achievement_set_id')->references('id')->on('achievement_sets')->onDelete('cascade');
            $table->foreign('achievement_id')->references('id')->on('achievements')->onDelete('cascade');
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
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('achievement_set_authors');
        Schema::dropIfExists('achievement_set_versions');
        Schema::dropIfExists('achievement_set_achievements');
        Schema::dropIfExists('achievement_sets');

        Schema::dropIfExists('achievement_authors');
        Schema::dropIfExists('achievements');
    }
};
