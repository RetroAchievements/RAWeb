<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        Schema::create('leaderboards', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedBigInteger('game_id');

            /*
             * patch -> leaderboard -> triggerable
             */
            // $table->text('patch')->nullable();

            $table->string('format', 50)->nullable();
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->boolean('rank_asc')->default(false);
            $table->unsignedInteger('order_column')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('game_id');

            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
        });

        Schema::create('leaderboard_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('leaderboard_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('score');
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['leaderboard_id', 'user_id']);
            $table->index('leaderboard_id');

            $table->foreign('leaderboard_id')->references('id')->on('leaderboards')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('leaderboard_entries');
        Schema::dropIfExists('leaderboards');
    }
};
