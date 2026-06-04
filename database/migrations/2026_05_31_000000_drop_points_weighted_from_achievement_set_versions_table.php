<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('achievement_set_versions', function (Blueprint $table) {
            $table->dropColumn('points_weighted');
        });
    }

    public function down(): void
    {
        Schema::table('achievement_set_versions', function (Blueprint $table) {
            $table->unsignedInteger('points_weighted')->nullable(false)->default(0)->after('points_total');
        });
    }
};
