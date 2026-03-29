<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_awards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('award_type', 30);
            $table->string('label', 40);
            $table->string('image_asset_path', 50)->nullable();
            $table->timestamps();

            $table->index('award_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_awards');
    }
};
