<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // These queries filter by a recent rich_presence_updated_at timestamp across
        // all games. Without this index, each query scans the full table and a cache
        // stampede can occur when the cache is flushed.
        Schema::table('game_recent_players', function (Blueprint $table) {
            $table->index('rich_presence_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('game_recent_players', function (Blueprint $table) {
            $table->dropIndex(['rich_presence_updated_at']);
        });
    }
};
