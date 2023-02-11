<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    protected $connection = 'mysql_legacy';

    public function up()
    {
        Schema::table('Ticket', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('Ticket');

            if (array_key_exists('achievementid_reportedbyuserid', $indexesFound)) {
                $table->dropUnique('achievementid_reportedbyuserid');
            }

            if (array_key_exists('ticket_achievementid_reportedbyuserid_unique', $indexesFound)) {
                $table->dropUnique(['AchievementID', 'ReportedByUserID']);
            }

            $table->index(['AchievementID', 'ReportedByUserID']);
        });
    }

    public function down()
    {
        Schema::table('Ticket', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('Ticket');

            if (array_key_exists('ticket_achievementid_reportedbyuserid_index', $indexesFound)) {
                $table->dropIndex('ticket_achievementid_reportedbyuserid_index');
            }
        });
    }
};
