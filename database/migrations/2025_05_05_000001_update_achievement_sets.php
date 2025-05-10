<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('achievement_sets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');

            $table->dateTime('achievements_published_at')->nullable()->default(null)->after('players_hardcore');
            $table->integer('times_completed')->default(0)->after('points_weighted');
            $table->integer('times_completed_hardcore')->default(0)->after('times_completed');
            $table->integer('median_time_to_complete')->nullable()->default(null)->after('times_completed_hardcore');
            $table->integer('median_time_to_complete_hardcore')->nullable()->default(null)->after('median_time_to_complete');
        });
    }

    public function down(): void
    {
        Schema::table('achievement_sets', function (Blueprint $table) {
            $table->dropColumn([
                'achievements_published_at',
                'times_completed',
                'times_completed_hardcore',
                'median_time_to_complete',
                'median_time_to_complete_hardcore',
            ]);

            $table->unsignedBigInteger('user_id')->nullable()->default(null)->after('id');
            $table->index(['user_id']);
            $table->foreign('user_id')
                ->references('ID')
                ->on('UserAccounts')
                ->onDelete('set null');
        });
    }
};
