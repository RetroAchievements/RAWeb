<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\System;
use App\Platform\Actions\GetPopularEmulatorIdsAction;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheMostPopularEmulators extends Command
{
    protected $signature = "ra:cache-most-popular-emulators 
                           {--system= : Optional system ID to process a single system}";
    protected $description = "Cache emulator popularity data for all active systems and overall popularity.";

    public function handle(): void
    {
        $action = new GetPopularEmulatorIdsAction();
        $systemId = $this->option('system');

        // If a specific system ID is provided, only process that system.
        if ($systemId) {
            $system = System::find($systemId);

            if (!$system) {
                $this->error("System with ID {$systemId} not found.");

                return;
            } elseif (!$system->active) {
                $this->warn("System with ID {$systemId} is not active.");

                return;
            }

            $this->info("Caching emulator popularity for {$system->name}...");

            $popularEmulatorIds = $action->execute($system);
            $cacheKey = "popular-emulators-for-system:{$system->id}";
            Cache::put($cacheKey, $popularEmulatorIds, now()->addDay());

            $this->info("Done. Cached [" . implode(', ', $popularEmulatorIds) . "].");

            return;
        }

        // Process all active systems.
        $this->info('Caching emulator popularity for all active systems...');

        $activeSystems = System::active()->whereNotIn('ID', [System::Hubs, System::Events, System::Standalones])->get();

        foreach ($activeSystems as $system) {
            try {
                $popularEmulatorIds = $action->execute($system);
                $cacheKey = "popular-emulators-for-system:{$system->id}";
                Cache::put($cacheKey, $popularEmulatorIds, now()->addDay());

                $this->newLine();
                $this->info("Cached [" . implode(', ', $popularEmulatorIds) . "] for [{$system->id}:{$system->name}].");
            } catch (Exception $e) {
                Log::error("Error caching emulator popularity for system {$system->name}: " . $e->getMessage());
            }
        }

        $this->newLine();

        // Process overall most popular emulators using a direct database query.
        $this->info('Calculating overall most popular emulators...');
        $overallPopularity = $action->execute();

        // Cache with ID 0 to represent overall popularity.
        $cacheKey = "popular-emulators-for-system:0";
        Cache::put($cacheKey, $overallPopularity, now()->addDay());

        $this->info("Cached overall most popular emulators [" . implode(', ', $overallPopularity) . "].");
        $this->info('Done.');
    }
}
