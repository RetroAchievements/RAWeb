<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('platforms', function (Blueprint $table) {
            $table->integer('order_column')->after('execution_environment')->default(0);
        });

        Schema::table('emulator_downloads', function (Blueprint $table) {
            $table->unique(['emulator_id', 'platform_id']);
        });
    }

    public function down(): void
    {
        Schema::table('emulator_downloads', function (Blueprint $table) {
            $table->dropUnique(['emulator_id', 'platform_id']);
        });

        Schema::table('platforms', function (Blueprint $table) {
            $table->dropColumn('order_column');
        });
    }
};
