<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::rename('GameHashLibrary', 'game_hashes');

        // These need to all be broken out into individual operations,
        // otherwise SQLite explodes during test runs.
        Schema::table('game_hashes', function (Blueprint $table) {
            $table->renameColumn('MD5', 'md5');
        });
        Schema::table('game_hashes', function (Blueprint $table) {
            $table->renameColumn('Name', 'name');
        });
        Schema::table('game_hashes', function (Blueprint $table) {
            $table->renameColumn('GameID', 'game_id');
        });
        Schema::table('game_hashes', function (Blueprint $table) {
            $table->renameColumn('Created', 'created_at');
        });
        Schema::table('game_hashes', function (Blueprint $table) {
            $table->renameColumn('Labels', 'labels');
        });

        Schema::table('game_hashes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('User');
            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });

        Schema::table('game_hashes', function (Blueprint $table) {
            $table->unsignedBigInteger('game_id')->change();
            $table->foreign('game_id')->references('ID')->on('GameData')->onDelete('cascade');
        });

        Schema::table('game_hashes', function (Blueprint $table) {
            $table->dropUnique('game_hashes_md5_unique');
        });

        Schema::table('game_hashes', function (Blueprint $table) {
            $table->unique('md5', 'game_hashes_md5_unique');
        });
    }

    public function down(): void
    {
        Schema::table('game_hashes', function (Blueprint $table) {
            $table->dropForeign(['game_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('game_hashes', function (Blueprint $table) {
            $table->unsignedInteger('game_id')->change();
        });

        Schema::table('game_hashes', function (Blueprint $table) {
            $table->dropUnique('game_hashes_md5_unique');

            $table->renameColumn('md5', 'MD5');
            $table->renameColumn('name', 'Name');
            $table->renameColumn('game_id', 'GameID');
            $table->renameColumn('created_at', 'Created');
            $table->renameColumn('labels', 'Labels');

            $table->dropColumn('user_id');
        });

        Schema::table('game_hashes', function (Blueprint $table) {
            $table->unique('MD5', 'game_hashes_md5_unique');
        });

        Schema::rename('game_hashes', 'GameHashLibrary');
    }
};
