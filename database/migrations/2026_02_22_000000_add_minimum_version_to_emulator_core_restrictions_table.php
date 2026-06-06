<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('emulator_core_restrictions', function (Blueprint $table) {
            $table->string('minimum_version')->nullable()->after('support_level');
        });
    }

    public function down(): void
    {
        Schema::table('emulator_core_restrictions', function (Blueprint $table) {
            $table->dropColumn('minimum_version');
        });
    }
};
