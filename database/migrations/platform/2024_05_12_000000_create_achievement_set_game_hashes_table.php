<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('achievement_set_game_hashes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('achievement_set_id');
            $table->unsignedBigInteger('game_hash_id');
            $table->boolean('compatible')->default(true);
            $table->timestamps();

            $table->foreign('achievement_set_id')->references('id')->on('achievement_sets')->onDelete('cascade');
            $table->foreign('game_hash_id')->references('id')->on('game_hashes')->onDelete('cascade');
            $table->unique(['achievement_set_id', 'game_hash_id'], 'set_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievement_set_game_hashes');
    }
};
