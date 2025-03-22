<?php

/*
 *  API_GetUserRecentAchievements - returns achievements recently earned by a user
 *    u : username or user ULID
 *    m : minutes to look back (default: 60)
 *
 *  array
 *   object    [value]                    an achievement that was earned
 *    datetime   Date                     when the achievement was earned
 *    int        HardcoreMode             1 if unlocked in hardcore, otherwise 0
 *    int        AchievementID            unique identifier of the achievement
 *    string     Title                    title of the achievement
 *    string     Description              description of the achievement
 *    int        Points                   number of points the achievement is worth
 *    int        TrueRatio                number of RetroPoints ("white points") the achievement is worth
 *    string     BadgeName                unique identifier of the badge image for the achievement
 *    string     BadgeURL                 site-relative path to the badge image for the achievement
 *    string     Type                     null, "progression", "win_condition", or "missable"
 *    string     Author                   user who originally created the achievement
 *    int        GameID                   unique identifier of the game associated to the achievement
 *    string     GameTitle                title of the game associated to the achievement
 *    string     GameIcon                 site-relative path to the game's icon image
 *    string     ConsoleName              name of the console associated to the game
 *    string     GameURL                  site-relative path to the game page
 */

use App\Actions\FindUserByIdentifierAction;
use App\Platform\Enums\UnlockMode;
use App\Support\Rules\ValidUserIdentifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'u' => ['required', new ValidUserIdentifier()],
    'm' => ['sometimes', 'integer', 'min:0', 'nullable'],
]);

$user = (new FindUserByIdentifierAction())->execute($input['u']);
if (!$user) {
    return response()->json([]);
}

$minutes = $input['m'] ?? 60;

$dateStart = Carbon::now()->subMinutes($minutes)->format('Y-m-d H:i:s');
$dateEnd = Carbon::now()->addMinutes(1)->format('Y-m-d H:i:s');

$data = getAchievementsEarnedBetween($dateStart, $dateEnd, $user);

// filter out softcore unlocks when hardcore unlocks are present
$hardcoreIDs = [];

foreach ($data as &$nextData) {
    if ($nextData['HardcoreMode'] == UnlockMode::Hardcore) {
        $hardcoreIDs[] = $nextData['AchievementID'];
    }

    $nextData['BadgeURL'] = "/Badge/" . $nextData['BadgeName'] . ".png";
    $nextData['GameURL'] = "/game/" . $nextData['GameID'];
    unset($nextData['CumulScore']);
}

$data = array_filter($data, function ($entry) use ($hardcoreIDs) {
    return $entry['HardcoreMode'] == UnlockMode::Hardcore || !in_array($entry['AchievementID'], $hardcoreIDs);
});

// return newest unlocks first
$data = array_reverse($data);

return response()->json($data);
