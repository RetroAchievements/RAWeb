<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('message_threads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->integer('num_messages')->default(0);
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_threads');
    }
};
