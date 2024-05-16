<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('LeaderboardEntry');
    }

    public function down(): void
    {
        DB::statement('
            CREATE TABLE `LeaderboardEntry` (
                `LeaderboardID` int(10) unsigned NOT NULL DEFAULT 0,
                `UserID` int(10) unsigned NOT NULL DEFAULT 0,
                `Score` int(11) NOT NULL DEFAULT 0,
                `DateSubmitted` datetime NOT NULL,
                `Created` timestamp NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`LeaderboardID`,`UserID`),
                KEY `leaderboardentry_leaderboardid_index` (`LeaderboardID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }
};
