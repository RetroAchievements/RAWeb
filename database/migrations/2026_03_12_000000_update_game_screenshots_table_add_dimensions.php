<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('game_screenshots', function (Blueprint $table) {
            $table->unsignedSmallInteger('width')->nullable()->after('media_id');
            $table->unsignedSmallInteger('height')->nullable()->after('width');

            $table->index(['game_id', 'width', 'height']);
        });
    }

    public function down(): void
    {
        Schema::table('game_screenshots', function (Blueprint $table) {
            $table->dropIndex(['game_id', 'width', 'height']);

            $table->dropColumn(['width', 'height']);
        });
    }
};
