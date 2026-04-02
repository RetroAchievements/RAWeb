<?php

declare(strict_types=1);

use App\Community\Enums\LookingForGroupInviteStatus;
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
        Schema::create('looking_for_group_invites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('looking_for_group_post_id')->index();
            $table->unsignedBigInteger('sender_user_id')->index();
            $table->unsignedBigInteger('recipient_user_id')->index();
            $table->enum('status', array_column(LookingForGroupInviteStatus::cases(), 'value'))->default(LookingForGroupInviteStatus::Pending->value);
            $table->text('message')->nullable();
            $table->timestamp('sent_at')->default(now());
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('looking_for_group_post_id')->references('id')->on('looking_for_group_posts')->onDelete('cascade');
            $table->foreign('sender_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('recipient_user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes for common queries
            $table->index(['status', 'expires_at']);
            $table->index(['sender_user_id', 'status']);
            $table->index(['recipient_user_id', 'status']);
            $table->index(['looking_for_group_post_id', 'status']);

            // Prevent duplicate pending invites for same user/post
            $table->unique(['looking_for_group_post_id', 'sender_user_id', 'status'], 'unique_lfg_pending_invites');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('looking_for_group_invites');
    }
};
