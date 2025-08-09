<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('api_version', 10); // 'internal', '1', '2'
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('endpoint', 255);
            $table->string('method', 10);
            $table->integer('response_code');
            $table->integer('response_time_ms')->nullable();
            $table->unsignedInteger('response_size_bytes')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->json('request_data')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('api_version');
            $table->index('created_at');
            $table->index(['api_version', 'endpoint']);
            $table->index(['user_id', 'created_at']);

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
