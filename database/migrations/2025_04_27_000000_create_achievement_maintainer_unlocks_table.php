<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('achievement_maintainer_unlocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('player_achievement_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('maintainer_id');
            $table->unsignedBigInteger('achievement_id');
            $table->timestamps();
        });

        Schema::table('achievement_maintainer_unlocks', function (Blueprint $table) {
            $table->foreign('player_achievement_id')
                ->references('id')
                ->on('player_achievements')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('ID')
                ->on('UserAccounts')
                ->onDelete('cascade');

            $table->foreign('maintainer_id')
                ->references('ID')
                ->on('UserAccounts')
                ->onDelete('cascade');

            $table->foreign('achievement_id')
                ->references('ID')
                ->on('Achievements')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievement_maintainer_unlocks');
    }
};
