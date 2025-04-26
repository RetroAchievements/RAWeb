<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('GameAlternatives');
    }

    public function down(): void
    {
        Schema::create('GameAlternatives', function (Blueprint $table) {
            $table->unsignedInteger('gameID')->nullable()->index();
            $table->unsignedInteger('gameIDAlt')->nullable()->index();
            $table->timestamp('Created')->nullable()->useCurrent();
            $table->timestamp('Updated')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }
};
