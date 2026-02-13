<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('emulator_core_restrictions', function (Blueprint $table) {
            $table->id();
            $table->string('core_name', 80)->unique();
            $table->unsignedTinyInteger('support_level');
            $table->string('recommendation')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emulator_core_restrictions');
    }
};
