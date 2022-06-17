<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;

class SyncPlayerBadges extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:player-badges {username?} {--f|full} {--p|no-post}';
    protected $description = 'Sync player badges';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sync('player_badges');
    }
}
