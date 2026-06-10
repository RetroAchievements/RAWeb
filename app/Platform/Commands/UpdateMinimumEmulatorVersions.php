<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\EmulatorUserAgent;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateMinimumEmulatorVersions extends Command
{
    protected $signature = 'ra:platform:emulator:update-minimum-versions';
    protected $description = 'Updates scheduled bumps to minimum version';

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $count = EmulatorUserAgent::query()
            ->where('pending_minimum_hardcore_version_at', '<=', Carbon::now())
            ->whereNotNull('pending_minimum_hardcore_version')
            ->update([
                'minimum_hardcore_version' => DB::raw('pending_minimum_hardcore_version'),
                'pending_minimum_hardcore_version' => null,
                'pending_minimum_hardcore_version_at' => null,
            ]);

        $this->info($count . ' ' . Str::plural('minimum version', $count) . ' updated.');
    }
}
