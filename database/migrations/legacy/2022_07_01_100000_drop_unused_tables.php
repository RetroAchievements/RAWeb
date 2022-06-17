<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        if (app()->environment('testing')) {
            return;
        }

        Schema::connection('mysql_legacy')->dropIfExists('PlaylistVideo');
        Schema::connection('mysql_legacy')->dropIfExists('ScoreHistory');
    }

    public function down()
    {
    }
};
