<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sync_status')) {
            return;
        }

        Schema::create('sync_status', function (Blueprint $table) {
            $table->string('kind');
            $table->string('reference')->nullable();
            $table->unsignedInteger('remaining')->nullable();
            $table->timestampTz('updated')->nullable();
            $table->primary('kind');
        });
    }

    public function down(): void
    {
        // no
    }
};
