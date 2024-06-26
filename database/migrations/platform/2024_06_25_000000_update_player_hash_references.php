<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_achievements', function (Blueprint $table) {
            $table->unsignedBigInteger('game_hash_id')->nullable()->after('trigger_id');
            $table->foreign('game_hash_id')->references('id')->on('game_hashes')->onDelete('set null');
        });

        Schema::table('leaderboard_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('game_hash_id')->nullable()->after('trigger_id');
            $table->foreign('game_hash_id')->references('id')->on('game_hashes')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('leaderboard_entries', function (Blueprint $table) {
            $table->dropForeign(['game_hash_id']);
            $table->dropColumn('game_hash_id');
        });

        Schema::table('player_achievements', function (Blueprint $table) {
            $table->dropForeign(['game_hash_id']);
            $table->dropColumn('game_hash_id');
        });
    }
};
