<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('websockets_statistics_entries');
    }

    public function down(): void
    {
        Schema::create('websockets_statistics_entries', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->string('app_id');
            $table->unsignedInteger('peak_connection_count');
            $table->unsignedInteger('websocket_message_count');
            $table->unsignedInteger('api_message_count');
            $table->timestampsTz();
        });
    }
};
