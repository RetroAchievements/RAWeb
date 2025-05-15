<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('GameData', function (Blueprint $table) {
            $table->integer('times_beaten')->default(0)->after('players_hardcore');
            $table->integer('times_beaten_hardcore')->default(0)->after('times_beaten');
            $table->integer('median_time_to_beat')->nullable()->default(null)->after('times_beaten_hardcore');
            $table->integer('median_time_to_beat_hardcore')->nullable()->default(null)->after('median_time_to_beat');
        });
    }

    public function down(): void
    {
        Schema::table('GameData', function (Blueprint $table) {
            $table->dropColumn([
                'times_beaten',
                'times_beaten_hardcore',
                'median_time_to_beat',
                'median_time_to_beat_hardcore',
            ]);
        });
    }
};
