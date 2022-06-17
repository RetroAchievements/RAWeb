<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;

class SyncRatings extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:ratings {--f|full} {--p|no-post}';

    protected $description = 'Sync ratings';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sync('ratings');
    }
}
