<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // The Parent Unlock Request
        // Primarily for linking the request, user, game and set.
        Schema::create('player_manual_unlock_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignId('achievement_set_id')->constrained('achievement_sets')->cascadeOnDelete();

            // Overall status of the request (e.g., pending, partial, approved, denied)
            $table->string('status', 50)->default('pending');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // Individual Achievement Requests/Items tied to the parent request
        // Contains details for each achievement, including individual status, proof, and hardcore flag.
        Schema::create('player_manual_unlock_request_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('unlock_request_id')->constrained('player_manual_unlock_requests')->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained('achievements')->cascadeOnDelete();

            $table->boolean('hardcore')->nullable(false)->default(false);
            $table->text('body')->nullable();

            $table->string('status', 50)->default('pending')->nullable(false);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_manual_unlock_request_items');
        Schema::dropIfExists('player_manual_unlock_requests');
    }
};
