<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\System;
use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SyncSystems extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:systems {--f|full} {--p|no-post}';
    protected $description = 'Sync systems';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sync('systems');
    }

    protected function query(): Builder
    {
        return DB::table('Console')
            ->select('*');
    }

    protected function postProcessEntity(object $origin, object $transformed): void
    {
        /** @var System $system */
        $system = System::find($transformed->id);

        $system->timestamps = false;
        $system->save();

        // dump($transformed);
        // dd($origin);

        /*
         * sync systems
         * TODO: make systems forumable
         */
    }
}
