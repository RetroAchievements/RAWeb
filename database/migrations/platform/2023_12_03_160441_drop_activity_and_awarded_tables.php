<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('Activity', function (Blueprint $table) {
            $table->dropIfExists();
        });

        Schema::table('Awarded', function (Blueprint $table) {
            $table->dropIfExists();
        });
    }

    public function down(): void
    {
        DB::statement('
            CREATE TABLE `Activity` (
                `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
                `lastupdate` timestamp NULL DEFAULT NULL,
                `activitytype` smallint(6) NOT NULL,
                `User` varchar(32) NOT NULL,
                `data` varchar(20) DEFAULT NULL,
                `data2` varchar(12) DEFAULT NULL,
                PRIMARY KEY (`ID`),
                KEY `activity_user_index` (`User`),
                KEY `activity_data_index` (`data`),
                KEY `activity_activitytype_index` (`activitytype`),
                KEY `activity_timestamp_index` (`timestamp`),
                KEY `activity_lastupdate_index` (`lastupdate`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci
        ');

        DB::statement('
            CREATE TABLE `Awarded` (
                `User` varchar(32) NOT NULL,
                `AchievementID` int(11) NOT NULL,
                `Date` timestamp NULL DEFAULT current_timestamp(),
                `HardcoreMode` tinyint(3) unsigned NOT NULL DEFAULT 0,
                PRIMARY KEY (`User`,`AchievementID`,`HardcoreMode`),
                KEY `awarded_user_index` (`User`),
                KEY `awarded_achievementid_index` (`AchievementID`),
                KEY `awarded_date_index` (`Date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci
        ');
    }
};
