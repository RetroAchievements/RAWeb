<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('sync_status');
    }

    public function down(): void
    {
        Schema::create('sync_status', function (Blueprint $table) {
            $table->string('kind')->primary();
            $table->string('reference')->nullable();
            $table->unsignedInteger('remaining')->nullable();
            $table->timestamp('updated')->nullable();
        });
    }
};
