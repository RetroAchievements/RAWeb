<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('message_thread_participants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('thread_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('num_unread')->default(0);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
            $table->foreign('thread_id')->references('ID')->on('message_threads')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_thread_participants');
    }
};
