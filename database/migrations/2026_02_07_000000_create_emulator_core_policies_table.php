<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('emulator_core_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('emulator_id');
            $table->string('core_name', 80)->index();
            $table->unsignedTinyInteger('support_level');
            $table->string('recommendation')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('emulator_id')->references('id')->on('emulators')->cascadeOnDelete();
            $table->unique(['emulator_id', 'core_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emulator_core_policies');
    }
};
