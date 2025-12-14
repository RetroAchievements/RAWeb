<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('player_stat_rankings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('system_id')->nullable();
            $table->string('kind', 40);
            $table->integer('total');
            $table->integer('rank_number');
            $table->integer('row_number');
            $table->unsignedBigInteger('last_game_id')->nullable();
            $table->timestamp('last_affected_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['system_id', 'kind', 'row_number'], 'player_stat_rankings_leaderboard_unique');
            $table->index(['user_id', 'system_id', 'kind'], 'player_stat_rankings_user_lookup_index');

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
            $table->foreign('system_id')->references('ID')->on('Console')->onDelete('cascade');
            $table->foreign('last_game_id')->references('ID')->on('GameData')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_stat_rankings');
    }
};
