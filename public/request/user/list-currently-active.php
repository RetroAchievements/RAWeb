<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

$currentlyActive = Cache::remember(
    'currently-active',
    Carbon::now()->addMinutes(2),
    fn () => collect(getLatestRichPresenceUpdates())
        ->keyBy('User')
        ->map(function ($user) {
            $user['InGame'] = true;

            return $user;
        })
        ->values()
        ->toArray()
);

return response()->json($currentlyActive);
