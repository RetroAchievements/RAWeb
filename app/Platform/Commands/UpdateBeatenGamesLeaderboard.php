<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\System;
use App\Platform\Enums\PlayerStatRankingKind;
use App\Platform\Jobs\UpdateBeatenGamesLeaderboardJob;
use Illuminate\Console\Command;

class UpdateBeatenGamesLeaderboard extends Command
{
    protected $signature = 'ra:platform:player:update-beaten-games-leaderboard
                            {--system= : Specific system ID, or "overall" for the overall leaderboard}';
    protected $description = 'Dispatch jobs to update the pre-computed beaten games leaderboard rankings';

    public function handle(): int
    {
        $systemIds = $this->getSystemIds();
        if ($systemIds === null) {
            return 1;
        }

        $numJobs = 0;
        foreach ($systemIds as $systemId) {
            foreach (PlayerStatRankingKind::beatenCases() as $kind) {
                UpdateBeatenGamesLeaderboardJob::dispatch($systemId, $kind)->onQueue('game-beaten-metrics');

                $numJobs++;
            }
        }

        $this->info("Dispatched {$numJobs} jobs to the game-beaten-metrics queue.");

        return 0;
    }

    /**
     * @return array<int|null>|null
     */
    private function getSystemIds(): ?array
    {
        $systemOption = $this->option('system');

        if ($systemOption === 'overall') {
            return [null];
        }

        if ($systemOption !== null) {
            $systemId = (int) $systemOption;
            if (!System::where('ID', $systemId)->exists()) {
                $this->error("System ID {$systemId} not found.");

                return null;
            }

            return [$systemId];
        }

        $systemIds = System::gameSystems()->active()->pluck('ID')->toArray();
        $systemIds[] = null;

        return $systemIds;
    }
}
