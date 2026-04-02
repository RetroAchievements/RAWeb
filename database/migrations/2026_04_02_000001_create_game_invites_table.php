<?php

declare(strict_types=1);

use App\Platform\Enums\GameInviteStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_invites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id')->index();
            $table->unsignedBigInteger('sender_user_id')->index();
            $table->unsignedBigInteger('recipient_user_id')->index();
            $table->enum('status', array_column(GameInviteStatus::cases(), 'value'))->default(GameInviteStatus::Pending->value);
            $table->text('message')->nullable();
            $table->timestamp('sent_at')->default(now());
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
            $table->foreign('sender_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('recipient_user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes for common queries
            $table->index(['status', 'expires_at']);
            $table->index(['sender_user_id', 'status']);
            $table->index(['recipient_user_id', 'status']);
            $table->index(['game_id', 'status']);

            // Prevent duplicate pending invites
            $table->unique(['sender_user_id', 'recipient_user_id', 'game_id', 'status'], 'unique_pending_invites');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_invites');
    }
};
