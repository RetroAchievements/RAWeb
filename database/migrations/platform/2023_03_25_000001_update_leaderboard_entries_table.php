<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    protected $connection = 'mysql_legacy';

    public function up()
    {
        if (!Schema::hasColumns('LeaderboardEntry', ['Video'])) {
            Schema::table('LeaderboardEntry', function (Blueprint $table) {
                $table->string('Video')->nullable()->after('Score');
            });
        }
    }

    public function down()
    {
        Schema::table('LeaderboardEntry', function (Blueprint $table) {
            $table->dropColumn('Video');
        });
    }
};
