<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;

class SyncGameSets extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:game-sets {--f|full} {--p|no-post}';
    protected $description = 'Sync game alternatives to sets';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sync('game_sets');
    }

    // protected function preProcessEntity(object $origin, array $transformed): array
    // {
    // }

    // protected function postProcessEntity(object $origin, object $transformed): void
    // {
    // }
}
