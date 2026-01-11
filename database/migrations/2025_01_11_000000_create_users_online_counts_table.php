<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users_online_counts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('online_count');
            $table->boolean('is_new_high')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index('online_count');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_online_counts');
    }
};
