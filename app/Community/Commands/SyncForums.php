<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;

class SyncForums extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:forums {id?} {--f|full} {--p|no-post}';

    protected $description = 'Sync forums';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sync('forums');
    }
}
