<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        /*
         * polymorphic "badgeables" with a badge
         *
         * user can receive badges
         * some badges are inherent, like achievement's - those are not attributed to a user directly
         *
         * thought about previously:
         * usually refer to something specific within a resource like event entries or achievement sets within a game
         * they "stack" within badge_sets
         *
         * cannot be lost - only be marked as invalid as a "feat of strength"
         * more or less "types" of badges which have multiple entries
         * individual set completions are badge entries, but the badge for completing one or more is an badge
         */
        Schema::create('badge_sets', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('title');
            $table->text('description');

            /*
             * type: stack, swap
             */

            /*
             * achievement sets have at least one badge set for achievement set completions
             * individual achievement set completion badges are in the badges table
             *
             * this makes badges stackable
             *
             * achievements_sets:
             *  - users can complete achievement sets multiple times in various versions
             *  - "completed an achievement set in a specific version"? (achievement_set_version)
             *
             * events:
             *  -
             *
             * "completed sets in this game" -> achievement_set
             * "won an aotw event" -> event
             * "won a leapfrog event" -> Event
             */
            $table->string('badgeable_model')->nullable();
            $table->unsignedBigInteger('badgeable_id')->nullable();

            /*
             * somebody may or may not own this set
             */
            $table->unsignedBigInteger('user_id')->nullable();

            /*
             * you can get an badge for:
             * - "winning" an event
             */

            /*
             * TODO: needed?
             */
            // $table->timestampTz('deprecated_at')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('user_id');
            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });

        /*
         * badges are concepts
         * badges track progress that can refer to a badge stage at a certain trigger
         *
         * compare to lbp pins
         * achievements = trophy
         *
         * for achievements:
         *  - are the "concept" of the achievement
         *  - refer to a particular stage of the badge
         *  - default: locked / unlocked
         */
        Schema::create('badges', function (Blueprint $table) {
            $table->bigIncrements('id');

            /*
             * badges may belong to one badge_set, which can belong to a specific resource: an achievements_sets, an event, ...
             * TODO: use badge_set_badges below instead?
             */
            $table->unsignedBigInteger('badge_set_id');

            /*
             * events:
             *  - "won this aotw"
             *  - "won this leapfrog"
             */
            $table->morphs('badgeable');

            /*
             * have a type, title, and description for badges that are not bound to an badgeable
             *
             * "is a patron"
             * "connected their twitch account"
             */
            $table->string('title')->nullable();
            $table->text('description')->nullable();

            $table->boolean('hidden')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();
        });

        /*
         * badge stages:
         * - track progress of something
         * - badges have a locked stage by default
         * - optional: stage can have a different locked state media -> useful if a badge has more than 1 achievement attached to a stage
         *
         * - the badge icon defaults to the lowest stage - may be overridden in higher stages
         *
         * - each stage may have a modifier
         *      - e.g. achievements have a modifier for softcore/hardcore which is set dynamically
         *
         * achievements:
         *  - locked (default from the badge)
         *  - unlocked - added for achievements by default
         *      - a softcore/hardcore modifier is set by the player_achievement given hardcore/softcore unlock
         *
         *  - unlocked with hardcore modifier - as the first and second stage
         *  - multiple achievements that refer to a badge's progress should be mergable:
         *     -
         */
        Schema::create('badge_stages', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('badge_id');

            $table->string('stage')->nullable();

            /*
             * polymorphic stages
             * e.g. achievements refer to a particular stage of the badge
             * e.g. a particular event date grants a stage within a badge
             */
            $table->morphs('badgeable');

            $table->string('title')->nullable();
            $table->text('description')->nullable();

            /*
             * badge stages may be revoked which will hide them from badges, cannot be unlocked anymore
             */
            $table->timestampTz('revoked_at');

            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('badge_set_badges', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('badge_set_id');
            $table->unsignedBigInteger('badge_id');

            $table->timestampsTz();
            // $table->softDeletesTz(); // TODO: soft deletes?

            $table->unique(['badge_set_id', 'badge_id']);

            $table->foreign('badge_set_id')->references('id')->on('badge_sets')->onDelete('cascade');
            $table->foreign('badge_id')->references('id')->on('badges')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badge_set_badges');
        Schema::dropIfExists('badge_stages');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('badge_sets');
    }
};
