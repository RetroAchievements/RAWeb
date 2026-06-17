<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('emulator_user_agents', function (Blueprint $table) {
            $table->string('pending_minimum_hardcore_version', 32)->nullable()->after('minimum_hardcore_version');
            $table->timestamp('pending_minimum_hardcore_version_at')->nullable()->after('pending_minimum_hardcore_version');
        });
    }

    public function down(): void
    {
        Schema::table('emulator_user_agents', function (Blueprint $table) {
            $table->dropColumn('pending_minimum_hardcore_version');
            $table->dropColumn('pending_minimum_hardcore_version_at');
        });
    }
};
