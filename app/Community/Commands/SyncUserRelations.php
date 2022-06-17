<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;

class SyncUserRelations extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:user-relations {--f|full} {--p|no-post}';

    protected $description = 'Sync user relations (friends, blocks)';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sync('user_relations');
    }
}
