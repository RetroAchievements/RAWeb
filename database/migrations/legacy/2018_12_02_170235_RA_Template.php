<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    protected $connection = 'mysql_legacy';

    public function up()
    {
        if (app()->environment('testing')) {
            $this->createTables();

            return;
        }

        // TODO to make tests work for legacy features either rewrite to Laravel schema migrations or get rid of legacy tables altogether
        $sql = file_get_contents(database_path('migrations/legacy/2012_10_03_133633_RA_Template.sql'));
        DB::connection($this->connection)->unprepared($sql);
    }

    public function down()
    {
    }

    private function createTables()
    {
        if (!Schema::hasTable('UserAccounts')) {
            Schema::create('UserAccounts', function (Blueprint $table) {
                $table->increments('id');
                $table->string('User');
                $table->timestamp('Deleted');
            });
        }
    }
};
