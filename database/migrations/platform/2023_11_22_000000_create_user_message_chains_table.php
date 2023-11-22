<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('user_message_chains', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->integer('num_messages')->default(0);
            $table->integer('sender_num_unread')->default(0);
            $table->integer('recipient_num_unread')->default(0);
            $table->timestampTz('sender_last_post_at')->nullable();
            $table->timestampTz('recipient_last_post_at')->nullable();
            $table->timestampTz('sender_deleted_at')->nullable();
            $table->timestampTz('recipient_deleted_at')->nullable();

            $table->index('sender_id');
            $table->index('recipient_id');

            $table->foreign('sender_id')->references('ID')->on('UserAccounts')->onDelete('set null');
            $table->foreign('recipient_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_message_chains');
    }
};
