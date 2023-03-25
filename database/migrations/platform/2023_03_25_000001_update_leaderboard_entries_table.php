<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    protected $connection = 'mysql_legacy';

    public function up()
    {
        if (!Schema::hasColumns('leaderboard_entries', ['video'])) {
            Schema::table('LeaderboardEntries', function (Blueprint $table) {
                $table->string('video')->nullable()->after('score');
            });
        }
    }

    public function down()
    {
        Schema::table('leaderboard_entries', function (Blueprint $table) {
            $table->dropColumn('video');
        });
    }
};
