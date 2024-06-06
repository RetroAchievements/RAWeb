<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\PlayerStat;
use App\Platform\Enums\PlayerStatType;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DeleteStalePlayerPointsStatsEntries extends Command
{
    protected $signature = 'ra:platform:player:delete-stale-points-stats
                            {days=7 : The number of days to keep}';
    protected $description = 'Delete any player points stats with values of 0 older than the specified number of days';

    public function handle(): void
    {
        $days = (int) $this->argument('days');
        $dateThreshold = Carbon::now()->subDays($days);

        $relevantPlayerStatTypes = [
            PlayerStatType::PointsHardcoreDay,
            PlayerStatType::PointsHardcoreWeek,
            PlayerStatType::PointsSoftcoreDay,
            PlayerStatType::PointsSoftcoreWeek,
            PlayerStatType::PointsWeightedDay,
            PlayerStatType::PointsWeightedWeek,
        ];
        PlayerStat::whereIn('type', $relevantPlayerStatTypes)
            ->where('value', 0)
            ->where('created_at', '<', $dateThreshold)
            ->delete();
    }
}
