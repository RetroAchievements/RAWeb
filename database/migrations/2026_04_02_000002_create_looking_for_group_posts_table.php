<?php

declare(strict_types=1);

use App\Community\Enums\LookingForGroupStatus;
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
        Schema::create('looking_for_group_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id')->index();
            $table->unsignedBigInteger('creator_user_id')->index();
            $table->string('title');
            $table->text('note')->nullable();
            $table->unsignedInteger('max_players')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->enum('status', array_column(LookingForGroupStatus::cases(), 'value'))->default(LookingForGroupStatus::Active->value);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
            $table->foreign('creator_user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes for common queries
            $table->index(['status', 'expires_at']);
            $table->index(['game_id', 'status']);
            $table->index(['creator_user_id', 'status']);
            $table->index(['scheduled_for']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('looking_for_group_posts');
    }
};
