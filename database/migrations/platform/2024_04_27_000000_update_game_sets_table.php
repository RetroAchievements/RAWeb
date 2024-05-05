<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('game_sets', function (Blueprint $table) {
            // Hubs were originally a special kind of Game entity.
            // To ensure backwards compatibility with existing URLs, we need to store
            // these legacy game IDs to best minimize disruption and SEO impact.
            $table->unsignedBigInteger('legacy_game_id')->nullable()->after('definition');
        });
    }

    public function down(): void
    {
        Schema::table('game_sets', function (Blueprint $table) {
            $table->dropColumn('legacy_game_id');
        });
    }
};
