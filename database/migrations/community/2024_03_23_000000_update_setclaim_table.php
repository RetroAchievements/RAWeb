<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        // [1] Rename GameID to game_id, establish foreign key relationship to GameData table.
        Schema::table('SetClaim', function (Blueprint $table) {
            $table->unsignedBigInteger('GameID')->change();
        });
        Schema::table('SetClaim', function (Blueprint $table) {
            $table->renameColumn('GameID', 'game_id');
        });
        Schema::table('SetClaim', function (Blueprint $table) {
            $table->foreign('game_id')->references('ID')->on('GameData')->onDelete('cascade');
        });

        // [2] Add user_id, establish foreign key relationship to UserAccounts table.
        Schema::table('SetClaim', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('user');
        });
        Schema::table('SetClaim', function (Blueprint $table) {
            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // [2] Add user_id, establish foreign key relationship to UserAccounts table.
        Schema::table('SetClaim', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        // [1] Rename GameID to game_id, establish foreign key relationship to GameData table.
        Schema::table('SetClaim', function (Blueprint $table) {
            $table->dropForeign(['game_id']);
            $table->renameColumn('game_id', 'GameID');
            $table->unsignedInteger('GameID')->change();
        });
    }
};
