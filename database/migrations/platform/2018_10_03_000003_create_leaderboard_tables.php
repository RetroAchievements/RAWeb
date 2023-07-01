<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('LeaderboardDef', function (Blueprint $table) {
            $table->bigIncrements('ID')->change();
            $table->unsignedBigInteger('GameID')->change();
            $table->string('Format', 50)->nullable()->change();
            $table->string('Title')->nullable()->change();
            $table->string('Description')->nullable()->change();

            $table->softDeletesTz();

            $table->foreign('GameID', 'leaderboards_game_id_foreign')->references('ID')->on('GameData')->onDelete('cascade');
        });

        // sync target for LeaderboardEntry
        Schema::create('leaderboard_entries', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('leaderboard_id');
            $table->unsignedBigInteger('user_id');

            $table->bigInteger('score');

            $table->unsignedBigInteger('trigger_id')->nullable();
            $table->unsignedBigInteger('player_session_id')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['leaderboard_id', 'user_id']);

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
            $table->foreign('trigger_id')->references('id')->on('triggers')->onDelete('set null');
            $table->foreign('player_session_id')->references('id')->on('player_sessions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_entries');

        Schema::table('LeaderboardDef', function (Blueprint $table) {
            $table->dropForeign('leaderboards_game_id_foreign');

            $table->dropSoftDeletesTz();
        });
    }
};
