<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
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
             * regardless of whether this is the default set
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

            $table->foreign('game_id')->references('ID')->on('GameData')->onDelete('cascade');
            $table->foreign('achievement_set_id')->references('id')->on('achievement_sets')->onDelete('cascade');
        });

        Schema::table('GameHashLibrary', function (Blueprint $table) {
            $table->dropPrimary('MD5');
        });

        Schema::table('GameHashLibrary', function (Blueprint $table) {
            // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->bigIncrements('id')->first();
            }
            $table->unsignedInteger('system_id')->nullable()->after('id');

            /*
             * the main identifier hash
             * usually md5 by a specific method
             */
            $table->string('hash')->after('system_id')->nullable();

            $table->string('type')->nullable()->after('hash');

            $table->string('crc', 8)->nullable()->after('type');
            $table->string('MD5', 32)->nullable()->change();
            $table->string('sha1', 40)->nullable()->after('MD5');
            $table->string('file_crc', 8)->nullable()->after('sha1');
            $table->string('file_md5', 32)->nullable()->after('file_crc');
            $table->string('file_sha1', 40)->nullable()->after('file_md5');
            $table->string('file_name_md5', 32)->nullable()->after('file_sha1');

            $table->string('description')->nullable()->after('Name');

            $table->json('file_names')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->json('regions')->nullable();
            $table->json('languages')->nullable();
            $table->string('source')->nullable();
            $table->string('source_status')->nullable();
            $table->string('source_version')->nullable();
            $table->timestampTz('imported_at')->nullable();

            $table->timestampTz('updated_at')->nullable();
            $table->softDeletesTz();

            $table->unique(['system_id', 'hash'], 'game_hashes_system_id_hash_unique');

            $table->unique('MD5', 'game_hashes_md5_unique');

            $table->foreign('system_id', 'game_hashes_system_id_foreign')->references('ID')->on('Console')->onDelete('cascade');
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

            $table->string('type')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('game_id')->references('ID')->on('GameData')->onDelete('cascade');
        });

        Schema::create('game_hash_set_hashes', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('game_hash_set_id');
            $table->unsignedBigInteger('game_hash_id');

            $table->timestampsTz();
            // $table->softDeletesTz(); // TODO: soft deletes?

            $table->unique(['game_hash_set_id', 'game_hash_id']);

            $table->foreign('game_hash_set_id')->references('id')->on('game_hash_sets')->onDelete('cascade');
            $table->foreign('game_hash_id')->references('id')->on('GameHashLibrary')->onDelete('cascade');
        });

        /*
         * game-hashes can have multiple notes per memory address - one is used as "the one"
         * used to be "code notes"
         * applied to a set of memory compatible game-hashes
         */
        Schema::table('CodeNotes', function (Blueprint $table) {
            $table->dropPrimary(['GameID', 'Address']);
        });

        Schema::table('CodeNotes', function (Blueprint $table) {
            // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->bigIncrements('id')->first();
            }
            $table->unsignedBigInteger('game_hash_set_id')->nullable()->after('id');

            $table->unsignedBigInteger('GameID')->nullable()->change();
            $table->unsignedBigInteger('AuthorID')->nullable()->change();

            $table->text('Note')->nullable()->change();

            $table->softDeletesTz();

            $table->index('Address', 'memory_notes_address_index');

            /*
             * Each developer can have their own code note
             */
            $table->index(['game_hash_set_id', 'Address'], 'memory_notes_game_hash_set_id_address_index');

            $table->foreign('game_hash_set_id', 'memory_notes_game_hash_set_id_foreign')->references('id')->on('game_hash_sets')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_achievement_sets');

        Schema::table('CodeNotes', function (Blueprint $table) {
            $table->dropForeign('memory_notes_game_hash_set_id_foreign');
            $table->dropIndex('memory_notes_address_index');
            $table->dropIndex('memory_notes_game_hash_set_id_address_index');

            $table->dropColumn('id');
            $table->dropColumn('game_hash_set_id');
            $table->dropSoftDeletesTz();
        });

        Schema::table('CodeNotes', function (Blueprint $table) {
            $table->primary(['GameID', 'Address']);
        });

        Schema::dropIfExists('game_hash_set_hashes');
        Schema::dropIfExists('game_hash_sets');

        Schema::table('GameHashLibrary', function (Blueprint $table) {
            $table->dropForeign('game_hashes_system_id_foreign');
            $table->dropUnique('game_hashes_md5_unique');
            $table->dropUnique('game_hashes_system_id_hash_unique');

            $table->dropColumn('id');
            $table->dropColumn('system_id');
            $table->dropColumn('hash');
            $table->dropColumn('type');
            $table->dropColumn('crc');
            $table->dropColumn('sha1');
            $table->dropColumn('file_crc');
            $table->dropColumn('file_md5');
            $table->dropColumn('file_sha1');
            $table->dropColumn('file_name_md5');
            $table->dropColumn('description');
            $table->dropColumn('file_names');
            $table->dropColumn('file_size');
            $table->dropColumn('parent_id');
            $table->dropColumn('regions');
            $table->dropColumn('languages');
            $table->dropColumn('source');
            $table->dropColumn('source_status');
            $table->dropColumn('source_version');
            $table->dropColumn('imported_at');
            $table->dropColumn('updated_at');
            $table->dropSoftDeletesTz();
        });

        Schema::table('GameHashLibrary', function (Blueprint $table) {
            $table->primary('MD5');
        });
    }
};
