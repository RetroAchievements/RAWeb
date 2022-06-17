<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;

class SyncLeaderboardEntries extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:leaderboard-entries {id?} {--f|full} {--p|no-post}';
    protected $description = 'Sync leaderboard entries';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sync('leaderboard_entries');
    }
}
