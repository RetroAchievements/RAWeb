<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->boolean('user_is_tracked')->default(1)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->boolean('user_is_tracked')->nullable()->change();
        });
    }
};
