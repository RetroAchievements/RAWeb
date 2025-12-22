<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_moderation_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('actioned_by_id')->nullable();
            $table->string('action');
            $table->text('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'created_at']);

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->cascadeOnDelete();
            $table->foreign('actioned_by_id')->references('ID')->on('UserAccounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_moderation_actions');
    }
};
