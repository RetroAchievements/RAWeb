<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_sessions', function (Blueprint $table) {
            $table->string('user_agent', 255)->nullable()->after('duration');
            $table->string('ip_addr', 40)->nullable()->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('player_sessions', function (Blueprint $table) {
            $table->dropColumn('ip_addr');
            $table->dropColumn('user_agent');
        });
    }
};
