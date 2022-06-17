<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;

class SyncLeaderboards extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:leaderboards {id?} {--f|full} {--p|no-post}';
    protected $description = 'Sync leaderboards';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sync('leaderboards');
    }
}
