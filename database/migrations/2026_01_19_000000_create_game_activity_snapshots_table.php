<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_activity_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id');
            $table->string('type');
            $table->decimal('score', 12, 2);
            $table->integer('player_count')->nullable();
            $table->decimal('trend_multiplier', 8, 2)->nullable();
            $table->string('trending_reason')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['type', 'created_at']);
            $table->index(['type', 'score']);

            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_activity_snapshots');
    }
};
