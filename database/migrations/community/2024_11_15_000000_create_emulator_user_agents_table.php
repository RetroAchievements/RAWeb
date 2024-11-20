<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('emulator_user_agents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('emulator_id');
            $table->string('client', 80);
            $table->string('minimum_allowed_version', 32)->nullable();
            $table->string('minimum_hardcore_version', 32)->nullable();
            $table->timestampsTz();
        });

        Schema::table('emulator_user_agents', function (Blueprint $table) {
            $table->foreign('emulator_id')->references('id')->on('emulators')->onDelete('cascade');

            $table->index('client');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emulator_user_agents');
    }
};
