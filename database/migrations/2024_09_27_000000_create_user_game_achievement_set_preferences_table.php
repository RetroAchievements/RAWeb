<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('user_game_achievement_set_preferences', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('game_achievement_set_id');
            $table->boolean('opted_in');
            $table->timestamps();
        });

        Schema::table('user_game_achievement_set_preferences', function (Blueprint $table) {
            $table->foreign('user_id', 'fk_user_gasp_user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
            $table->foreign('game_achievement_set_id', 'fk_user_gasp_game_ach_set_id')->references('id')->on('game_achievement_sets')->onDelete('cascade');

            $table->unique(['user_id', 'game_achievement_set_id'], 'unique_user_gasp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_game_achievement_set_preferences');
    }
};
