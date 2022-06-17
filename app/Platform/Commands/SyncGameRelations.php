<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;

class SyncGameRelations extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:game-relations {--f|full} {--p|no-post}';
    protected $description = 'Sync game relations';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sync('game_relations');
    }
}
