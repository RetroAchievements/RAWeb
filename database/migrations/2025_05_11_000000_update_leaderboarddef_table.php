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
            $table->unsignedBigInteger('top_user_id')->nullable()->after('top_entry_id');
            $table->unsignedBigInteger('top_score')->nullable()->after('top_user_id');
            $table->timestamp('top_entry_updated_at')->nullable()->after('top_score');

            $table->foreign('top_entry_id')
                ->references('id')
                ->on('leaderboard_entries')
                ->onDelete('set null');

            $table->foreign('top_user_id')
                ->references('id')
                ->on('UserAccounts')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('LeaderboardDef', function (Blueprint $table) {
            $table->dropForeign(['top_entry_id']);
            $table->dropForeign(['top_user_id']);
            $table->dropColumn(['top_entry_id', 'top_user_id', 'top_score', 'top_entry_updated_at']);
        });
    }
};
