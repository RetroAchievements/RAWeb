<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use Illuminate\Console\Command;

class DeleteOrphanedLeaderboardEntries extends Command
{
    protected $signature = 'ra:platform:delete-orphaned-leaderboard-entries';
    protected $description = 'Delete orphaned leaderboard entries';

    public function handle(): void
    {
        s_mysql_query("DELETE le FROM LeaderboardEntry le LEFT JOIN UserAccounts ua ON le.UserID = ua.ID WHERE ua.User IS NULL");
    }
}
