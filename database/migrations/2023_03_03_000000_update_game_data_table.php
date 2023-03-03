<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    protected $connection = 'mysql_legacy';

    public function up()
    {
        if (!Schema::hasColumns('GameData', ['GuideURL'])) {
            Schema::table('GameData', function (Blueprint $table) {
                $table->string('GuideURL')->nullable();
            });
        }
    }

    public function down()
    {
        Schema::table('GameData', function (Blueprint $table) {
            $table->dropColumn('GuideURL');
        });
    }
};