<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('playtest_awards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('label', 40);
            $table->string('image_asset_path', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playtest_awards');
    }
};
