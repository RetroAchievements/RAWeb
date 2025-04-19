<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('GameData', function (Blueprint $table) {
            $table->dateTime('achievements_published_at')->nullable()->default(null)->after('RichPresencePatch');
            $table->integer('times_beaten')->default(0)->after('TotalTruePoints');
            $table->integer('times_beaten_hardcore')->default(0)->after('times_beaten');
            $table->integer('times_completed')->default(0)->after('times_beaten_hardcore');
            $table->integer('times_completed_hardcore')->default(0)->after('times_completed');
            $table->integer('median_time_to_beat')->nullable()->default(null)->after('times_completed_hardcore');
            $table->integer('median_time_to_beat_hardcore')->nullable()->default(null)->after('median_time_to_beat');
            $table->integer('median_time_to_complete')->nullable()->default(null)->after('median_time_to_beat_hardcore');
            $table->integer('median_time_to_complete_hardcore')->nullable()->default(null)->after('median_time_to_complete');
        });
    }

    public function down(): void
    {
        Schema::table('GameData', function (Blueprint $table) {
            $table->dropColumn([
                'achievements_published_at',
                'times_beaten',
                'times_beaten_hardcore',
                'times_completed',
                'times_completed_hardcore',
                'median_time_to_beat',
                'median_time_to_beat_hardcore',
                'median_time_to_complete',
                'median_time_to_complete_hardcore',
            ]);
        });
    }
};
