<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_screenshots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignId('media_id')->unique()->constrained('media')->cascadeOnDelete();

            $table->string('type', 20); // ScreenShotType enum
            $table->boolean('is_primary')->default(false); // Is this the primary in-game screenshot?
            $table->string('status', 20)->default('approved'); // GameScreenshotStatus enum
            $table->text('description')->nullable();

            $table->foreignId('captured_by_user_id')->nullable()->constrained('users')->nullOnDelete(); // The user who uploaded this (nullable due to an upcoming backfill)
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete(); // The user who reviewed this upload (if any)

            $table->unsignedInteger('order_column')->default(0);
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            // Find a game's primary screenshot(s) by type.
            $table->index(['game_id', 'type', 'is_primary']);

            // Show a game's approved and ordered screenshots by type.
            $table->index(['game_id', 'type', 'status', 'order_column']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_screenshots');
    }
};
