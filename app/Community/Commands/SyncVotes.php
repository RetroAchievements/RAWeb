<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;

class SyncVotes extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:votes {id?} {--f|full} {--p|no-post}';

    protected $description = 'Sync votes';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sync('votes');
    }
}
