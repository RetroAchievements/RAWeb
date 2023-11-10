<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_sessions', function (Blueprint $table) {
            $table->index(['game_id', 'user_id', 'rich_presence_updated_at']);
            $table->index(['user_id', 'game_id', 'rich_presence_updated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('player_sessions', function (Blueprint $table) {
            $table->dropIndex(['game_id', 'user_id', 'rich_presence_updated_at']);
            $table->dropIndex(['user_id', 'game_id', 'rich_presence_updated_at']);
        });
    }
};
