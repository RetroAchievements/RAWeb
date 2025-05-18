<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('LeaderboardDef', function (Blueprint $table) {
            $table->unsignedBigInteger('top_entry_id')->nullable()->after('trigger_id');

            $table->foreign('top_entry_id')
                ->references('id')
                ->on('leaderboard_entries')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('LeaderboardDef', function (Blueprint $table) {
            $table->dropForeign(['top_entry_id']);
            $table->dropColumn(['top_entry_id']);
        });
    }
};
