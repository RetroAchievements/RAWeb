<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('game_achievement_sets', function (Blueprint $table) {
            $table->unique(['game_id', 'title']);
        });
    }

    public function down(): void
    {
        Schema::table('game_achievement_sets', function (Blueprint $table) {
            $table->dropUnique(['game_id', 'title']);
        });
    }
};
