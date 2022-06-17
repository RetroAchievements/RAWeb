<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        if (app()->environment('testing')) {
            return;
        }

        // might've been included in a dump
        if (Schema::connection('mysql_legacy')->hasColumn('SetRequest', 'Created')) {
            return;
        }

        Schema::connection('mysql_legacy')->table('SetRequest', function (Blueprint $table) {
            $table->timestampTz('Created')->nullable()->after('GameID');
        });
    }

    public function down()
    {
    }
};
