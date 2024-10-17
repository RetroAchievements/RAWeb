<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::rename('achievement_set_game_hashes', 'achievement_set_incompatible_game_hashes');

        Schema::table('achievement_set_incompatible_game_hashes', function (Blueprint $table) {
            $table->dropColumn('compatible');
        });
    }

    public function down(): void
    {
        Schema::table('achievement_set_incompatible_game_hashes', function (Blueprint $table) {
            $table->tinyInteger('compatible')->default(1);
        });

        Schema::rename('achievement_set_incompatible_game_hashes', 'achievement_set_game_hashes');
    }
};
