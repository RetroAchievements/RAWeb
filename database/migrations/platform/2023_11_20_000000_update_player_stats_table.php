<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_stats', function (Blueprint $table) {
            $table->timestampTz('last_affected_at')->nullable()->after('last_game_id');
        });
    }

    public function down(): void
    {
        Schema::table('player_stats', function (Blueprint $table) {
            $table->dropColumn('last_affected_at');
        });
    }
};
