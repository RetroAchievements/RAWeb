<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('emulators', function (Blueprint $table) {
            $table->boolean('can_debug_triggers')->after('active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('emulators', function (Blueprint $table) {
            $table->dropColumn('can_debug_triggers');
        });
    }
};
