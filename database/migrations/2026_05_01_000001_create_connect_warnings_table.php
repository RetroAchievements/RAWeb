<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('connect_warnings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('method', 32);
            $table->foreignId('player_session_id')->nullable()->constrained('player_sessions')->cascadeOnDelete();
            $table->string('username', 64);
            $table->string('related_type', 32);
            $table->unsignedBigInteger('related_id');
            $table->tinyInteger('hardcore');
            $table->integer('offset')->nullable();
            $table->integer('extra')->nullable();
            $table->string('validation_hash', 32);
            $table->string('smells');
            $table->string('user_agent');
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connect_warnings');
    }
};
