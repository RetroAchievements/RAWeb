<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->dropColumn('update_status');
        });
    }

    public function down(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->string('update_status')->nullable()->after('achievement_set_version_hash');
        });
    }
};
