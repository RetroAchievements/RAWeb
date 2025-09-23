<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('discord_message_thread_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_thread_id');
            $table->string('discord_thread_id', 20); // Discord snowflakes are 64-bit integers (max 20 digits)
            $table->timestamps();

            $table->index('discord_thread_id');

            $table->foreign('message_thread_id')->references('id')->on('message_threads')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discord_message_thread_mappings');
    }
};
