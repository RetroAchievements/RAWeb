<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\PlayerStat;
use App\Platform\Enums\PlayerStatType;
use Illuminate\Console\Command;

class DeleteStalePlayerPointsStatsEntries extends Command
{
    protected $signature = 'ra:platform:player:delete-stale-points-stats';
    protected $description = 'Delete any player points stats with values of 0';

    public function handle(): void
    {
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
            ->delete();
    }
}
