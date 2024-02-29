<?php

use App\Models\System;
use App\Platform\Enums\SystemFlag;
use Illuminate\Database\Eloquent\Collection;

/**
 * @return Collection<int, System>
 */
function getSystemsData(
    int $activeFlag = SystemFlag::AllSystems,
    int $gamesConsoleFlag = 0,
): Collection {
    $result = match ($activeFlag) {
        SystemFlag::ActiveSystems => System::where('active', SystemFlag::ActiveSystems)->get(),
        default => System::all(),
    };

    if ($gamesConsoleFlag == 1) {
        $result = $result->filter(fn ($system) => System::isGameSystem($system->ID));
    }

    return $result;
}
