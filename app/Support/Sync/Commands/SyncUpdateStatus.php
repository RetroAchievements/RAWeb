<?php

declare(strict_types=1);

namespace App\Support\Sync\Commands;

use App\Support\Sync\SyncStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncUpdateStatus extends Command
{
    protected $signature = 'ra:sync:status';

    protected $description = 'Update sync status of all kinds';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $syncs = config('sync.kinds');

        foreach ($syncs as $kind => $options) {
            $status = SyncStatus::where('kind', $kind)->firstOrCreate(['kind' => $kind]);

            $this->info('Update status: ' . $kind);

            if (empty($options['reference_key']) || empty($options['reference_table'])) {
                continue;
            }

            $hasKeyAsColumn = Schema::hasColumn($options['reference_table'], $options['reference_key']);

            if (!$hasKeyAsColumn) {
                continue;
            }

            $remaining = DB::table($options['reference_table'])
                ->where($options['reference_key'], '>', $status->getAttribute('reference') ?? '0000-00-00 00:00:00')
                ->orderBy($options['reference_key'])
                ->count();

            $this->info(' -> ' . $remaining);

            $status->fill(['remaining' => $remaining]);
            $status->save();
        }
    }
}
