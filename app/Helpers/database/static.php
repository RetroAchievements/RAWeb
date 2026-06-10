<?php

use App\Models\StaticData;
use Illuminate\Support\Facades\DB;

/**
 * @deprecated
 */
function static_addnewachievement(int $id): void
{
    StaticData::query()->update([
        'NumAchievements' => DB::raw('NumAchievements + 1'),
        'LastCreatedAchievementID' => $id,
    ]);
}

/**
 * @deprecated
 */
function static_addnewregistereduser(string $user): void
{
    StaticData::query()->update([
        'NumRegisteredUsers' => DB::raw('NumRegisteredUsers + 1'),
        'LastRegisteredUser' => $user,
        'LastRegisteredUserAt' => now(),
    ]);
}

/**
 * @deprecated
 */
function static_setlastupdatedgame(int $id): void
{
    StaticData::query()->update(['LastUpdatedGameID' => $id]);
}

/**
 * @deprecated
 */
function static_setlastupdatedachievement(int $id): void
{
    StaticData::query()->update(['LastUpdatedAchievementID' => $id]);
}
