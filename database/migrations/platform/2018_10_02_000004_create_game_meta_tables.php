<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        /*
         * achievements may be in multiple sets within a game
         */
        Schema::create('game_achievement_sets', function (Blueprint $table) {
            $table->bigIncrements('id');

            /*
             * to have an achievement appear in another game -> copy
             */
            $table->unsignedBigInteger('game_id');
            $table->unsignedBigInteger('achievement_set_id');

            /*
             * whether or not this is the default set
             * core (official)
             * bonus (official)
             * unofficial (promoted community achievements, depending on dev rank)
             * community (anything a user wants, including their own achievements)
             */
            $table->string('type')->nullable();

            $table->string('title')->nullable();

            $table->unsignedInteger('order_column')->nullable();

            /*
             * created_at -> added to the set initially
             * updated_at -> added to the set, touch each time it's added back
             * deleted_at -> removed from the set
             */
            $table->timestampsTz();
            // $table->softDeletesTz();

            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
            $table->foreign('achievement_set_id')->references('id')->on('achievement_sets')->onDelete('cascade');
        });

        // /**
        //  * games may have multiple badge sets
        //  * which in turn have multiple badges
        //  * which in turn have multiple stages which an achievement may refer to
        //  */
        // Schema::create('game_badge_sets', function (Blueprint $table) {
        //     $table->bigIncrements('id');
        //     $table->unsignedBigInteger('game_id');
        //     $table->unsignedBigInteger('badge_set_id');
        //
        //     $table->string('title')->nullable();
        //
        //     /**
        //      * type: achievements,
        //      */
        //     $table->string('type')->nullable();
        //
        //     // /**
        //     //  * whether or not this is the default set
        //     //  */
        //     // $table->boolean('default')->nullable();
        //
        //     $table->timestampsTz();
        //     $table->softDeletesTz();
        //
        //     $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
        //     $table->foreign('badge_set_id')->references('id')->on('badge_sets')->onDelete('cascade');
        // });

        Schema::create('game_hashes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('system_id');

            /*
             * the main identifier hash
             * usually md5 by a specific method
             */
            $table->string('hash');

            /*
             * TODO: what does type do again?
             */
            $table->string('type')->nullable();

            $table->string('crc', 8)->nullable();
            $table->string('md5', 32)->nullable();
            $table->string('sha1', 40)->nullable();
            $table->string('file_crc', 8)->nullable();
            $table->string('file_md5', 32)->nullable();
            $table->string('file_sha1', 40)->nullable();
            $table->string('file_name_md5', 32)->nullable();
            $table->json('file_names')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->json('regions')->nullable();
            $table->json('languages')->nullable();
            $table->string('source')->nullable();
            $table->string('source_status')->nullable();
            $table->string('source_version')->nullable();
            $table->timestampTz('imported_at')->nullable();

            /*
             * earlier creation dates are not known
             */
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['system_id', 'hash']);

            $table->foreign('system_id')->references('id')->on('systems')->onDelete('cascade');
        });

        /*
         * if a game is incompatible or should have a different set of achievements (regional variants)
         *
         * a game hash set should contain game-hashes that are completely memory compatible
         * yet, a game may have multiple assigned, given the achievement triggers can handle the memory differences
         * memory comments, tickets etc may reference a specific game hash set to make it clear what developers should look for
         */
        Schema::create('game_hash_sets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('game_id');

            /*
             * whether or not this is a compatible set
             * it should include the us rom's hash if given
             */
            $table->boolean('compatible')->nullable();

            /*
            * TODO: what is type for again?
            */
            $table->string('type')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
        });

        Schema::create('game_hash_set_hashes', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('game_hash_set_id');
            $table->unsignedBigInteger('game_hash_id');

            $table->timestampsTz();
            // $table->softDeletesTz(); // TODO: soft deletes?

            $table->unique(['game_hash_set_id', 'game_hash_id']);

            $table->foreign('game_hash_set_id')->references('id')->on('game_hash_sets')->onDelete('cascade');
            $table->foreign('game_hash_id')->references('id')->on('game_hashes')->onDelete('cascade');
        });

        /*
         * game-hashes can have multiple notes per memory address - one is used as "the one"
         * used to be "code notes"
         * applied to a set of memory compatible game-hashes
         */
        Schema::create('memory_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('game_hash_set_id');

            $table->unsignedBigInteger('address');

            $table->unsignedBigInteger('user_id')->nullable();

            $table->text('body')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('address');

            /*
             * Each developer can have their own code note
             */
            $table->unique(['game_hash_set_id', 'address', 'user_id']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('game_hash_set_id')->references('id')->on('game_hash_sets')->onDelete('cascade');
        });

        Schema::create('game_relations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('game_id')->nullable();
            $table->unsignedBigInteger('related_game_id')->nullable();
            $table->timestampsTz();

            $table->unique(['game_id', 'related_game_id']);
            $table->index('game_id');
            $table->index('related_game_id');

            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
            $table->foreign('related_game_id')->references('id')->on('games')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('game_relations');
        Schema::dropIfExists('memory_notes');
        Schema::dropIfExists('game_hash_set_hashes');
        Schema::dropIfExists('game_hash_sets');
        Schema::dropIfExists('game_hashes');
        Schema::dropIfExists('game_achievement_sets');
    }
};
