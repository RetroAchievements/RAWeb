<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        if (app()->environment('testing')) {
            return;
        }

        // might've been included in a dump
        if (Schema::connection('mysql_legacy')->hasColumns('Subscription', ['Created', 'Updated'])) {
            return;
        }

        Schema::connection('mysql_legacy')->table('Subscription', function (Blueprint $table) {
            $table->timestampTz('Created')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestampTz('Updated')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    public function down()
    {
    }
};
