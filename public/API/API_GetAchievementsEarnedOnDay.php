<?php

/*
 *  API_GetAchievementsEarnedOnDay - returns achievements earned by a user between two timestamps
 *    u : username or user ULID
 *    d : date (YYYY-MM-DD)
 *
 *  array
 *   object    [value]                    an achievement that was earned
 *    datetime   Date                     when the achievement was earned
 *    int        HardcoreMode             1 if unlocked in hardcore, otherwise 0
 *    int        AchievementID            unique identifier of the achievement
 *    string     Title                    title of the achievement
 *    string     Description              description of the achievement
 *    int        Points                   number of points the achievement is worth
 *    string     Type                     null, "progression", "win_condition", or "missable"
 *    string     BadgeName                unique identifier of the badge image for the achievement
 *    string     BadgeURL                 site-relative path to the badge image for the achievement
 *    string     Author                   user who originally created the achievement
 *    int        GameID                   unique identifier of the game associated to the achievement
 *    string     GameTitle                title of the game associated to the achievement
 *    string     GameIcon                 site-relative path to the game's icon image
 *    string     ConsoleName              name of the console associated to the game
 *    int        CumulScore               sum of points for all achievements so far (including current)
 *    string     GameURL                  site-relative path to the game page
 */

use App\Actions\FindUserByIdentifierAction;
use App\Support\Rules\ValidUserIdentifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'u' => ['required', new ValidUserIdentifier()],
    'd' => ['required', 'date'],
]);

$user = (new FindUserByIdentifierAction())->execute($input['u']);
if (!$user) {
    return response()->json([]);
}

$dateInput = request()->query('d');

$data = getAchievementsEarnedOnDay(strtotime($dateInput), $user);

foreach ($data as &$nextData) {
    $nextData['BadgeURL'] = "/Badge/" . $nextData['BadgeName'] . ".png";
    $nextData['GameURL'] = "/game/" . $nextData['GameID'];
}

return response()->json($data);
