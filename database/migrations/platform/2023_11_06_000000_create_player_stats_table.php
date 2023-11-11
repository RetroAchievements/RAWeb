<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        /**
         * A flexible structure to store different types of player stats data within the same table.
         * The 'type' column is a string that specifies the category of the statistic, such as
         * 'games_beaten_retail', and the 'value' column stores the numerical value associated
         * with that statistic. This allows for a wide range of aggregate stat types to be managed
         * dynamically without requiring schema changes for each new stat category.
         */
        Schema::create('player_stats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('system_id')->nullable(); // useful for system-specific leaderboards
            $table->unsignedBigInteger('last_game_id')->nullable(); // useful for showing most recent game which affected the value
            $table->string('type');
            $table->integer('value')->default(0);
            $table->timestampsTz();

            $table->unique(['user_id', 'system_id', 'type']);
            $table->index('user_id');
            $table->index('system_id');
            $table->index('type');

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
            $table->foreign('system_id')->references('ID')->on('Console')->onDelete('cascade');
            $table->foreign('last_game_id')->references('ID')->on('GameData')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rankings');
    }
};
