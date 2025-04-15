<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('achievement_sets', function (Blueprint $table) {
            $table->string('image_asset_path', 50)->after('points_weighted')->default('/Images/000001.png');
        });
    }

    public function down(): void
    {
        Schema::table('achievement_sets', function (Blueprint $table) {
            $table->dropColumn('image_asset_path');
        });
    }
};
