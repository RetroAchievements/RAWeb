<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('game_recent_players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id');
            $table->unsignedBigInteger('user_id');
            $table->text('rich_presence')->nullable();
            $table->timestamp('rich_presence_updated_at');

            $table->unique(['game_id', 'user_id']);
            $table->index(['game_id', 'rich_presence_updated_at'], 'idx_game_updated');

            $table->foreign('game_id')
                ->references('ID')
                ->on('GameData')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('ID')
                ->on('UserAccounts')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_recent_players');
    }
};
