<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_game_badge_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();

            // the chosen badge is referenced by content hash, not by game_badges.id. a backfill
            // layer delete-and-recreate would hand out fresh auto-increment ids and silently
            // repoint preferences. the sha1 is stable because the file bytes don't change.
            $table->string('sha1', 40);

            $table->timestamps();

            // a user has at most one preference per game. also covers the render lookup,
            // which joins on (user_id, game_id).
            $table->unique(['user_id', 'game_id']);

            // covers badge-removal cleanup, which deletes by badge content across all users.
            $table->index(['game_id', 'sha1']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_game_badge_preferences');
    }
};
