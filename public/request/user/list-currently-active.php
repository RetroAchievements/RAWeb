<?php

use Illuminate\Support\Facades\Cache;

$ttlSeconds = 60 * 2;

$currentlyActive = Cache::remember('currently-active', $ttlSeconds, function () {
    $ingameList = getLatestRichPresenceUpdates();
    $mergedList = [];

    foreach ($ingameList as $playerIngame) {
        // Array merge/overwrite
        $mergedList[$playerIngame['User']] = $playerIngame;
        $mergedList[$playerIngame['User']]['InGame'] = true;
    }

    return array_values($mergedList);
});

return response()->json($currentlyActive);
