<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('title')->nullable();
            $table->text('body')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('read_at')->nullable();

            $table->timestamp('recipient_deleted_at', 0)->nullable();
            $table->timestamp('sender_deleted_at', 0)->nullable();

            $table->softDeletesTz();

            $table->index('read_at');

            $table->foreign('recipient_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('messages');
    }
};
