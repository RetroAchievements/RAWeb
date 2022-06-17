<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Support\Sync\SyncTrait;
use Illuminate\Console\Command;

class SyncTickets extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:tickets {id?} {--f|full} {--p|no-post}';

    protected $description = 'Sync tickets';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->sync('tickets');
    }
}
