<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('downloads_popularity_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // eg: 'top-systems', 'popular-emulators-for-system:0', 'popular-emulators-for-system:1'
            $table->json('ordered_ids'); // JSON array of IDs in order of popularity.
            $table->timestamps(); // so we can track when recalculations occur.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downloads_popularity_metrics');
    }
};
