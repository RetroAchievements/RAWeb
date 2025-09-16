<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\GameSet;
use App\Models\GameSetLink;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class EventHubIdCacheService
{
    /**
     * @return array<int, int>
     */
    public static function getEventHubIds(): array
    {
        return Cache::flexible('event_hub_ids:v2', [
            86_400,  // Fresh for 24 hours.
            604_800, // We can serve it stale indefinitely, it doesn't really matter.
        ], function () {
            $childEventHubIds = GameSetLink::query()
                ->whereIn('parent_game_set_id', [
                    GameSet::CommunityEventsHubId,
                    GameSet::DeveloperEventsHubId,
                ])
                ->pluck('child_game_set_id');

            $titleEventHubIds = GameSet::where('title', 'like', '%Events -%')
                ->pluck('id');

            return $childEventHubIds->merge($titleEventHubIds)->unique()->values()->all();
        });
    }

    /**
     * Clear the cache when event hubs change.
     */
    public static function clearCache(): void
    {
        Cache::forget('event_hub_ids');
    }
}
