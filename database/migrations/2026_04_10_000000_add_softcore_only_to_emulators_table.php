<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('emulators', function (Blueprint $table) {
            $table->boolean('softcore_only')->default(false)->after('can_debug_triggers');
        });
    }

    public function down(): void
    {
        Schema::table('emulators', function (Blueprint $table) {
            $table->dropColumn('softcore_only');
        });
    }
};
