<?php

/*
 *  API_GetUserAwards - returns information about the user's earned awards
 *    u : username
 *
 *  int        TotalAwardsCount        number of awards earned by the user, including hidden
 *  int        HiddenAwardsCount       number of awards hidden by the user
 *  int        MasteryAwardsCount      number of game mastery awards earned by the user
 *  int        CompletionAwardsCount   number of game completion awards earned by the user (softcore mastery)
 *  int        EventAwardsCount        number of awards currently appearing in the user's Event Awards section
 *  int        SiteAwardsCount         number of awards currently appearing in the user's Site Awards section
 *  array      VisibleUserAwards
 *   datetime   AwardedAt              when the user earned the award
 *   string     AwardType              type of award
 *   int        AwardData              typically an ID, such as for a game
 *   int        AwardDataExtra         "1" if it's a Mastery, not a Completion
 *   int        DisplayOrder           order the award appears on the user's profile. -1 (Hidden) are omitted from this list
 *   string     Title                  name of the award, such as the game name if a Mastery/Completion
 *   string     ConsoleName            name of the console associated with the award
 *   int        Flags                  always "0" or null
 *   string     ImageIcon              site-relative path to the award's icon image
 */

declare(strict_types=1);

use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use LegacyApp\Community\Enums\AwardType;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'u' => ['required', 'min:2', 'max:20', new CtypeAlnum()],
]);

$user = request()->query('u');

$userAwards = getUsersSiteAwards($user);
[$gameAwards, $eventAwards, $siteAwards] = SeparateAwards($userAwards);

$masteryCount = 0;
$completionCount = 0;
$eventsCount = count($eventAwards);
$siteAwardsCount = count($siteAwards);
$onlyVisibleUserAwards = [];

foreach ($gameAwards as $gameAward) {
    // `AwardDataExtra` being 1 signifies the game award is a hardcore mastery.
    if ($gameAward['AwardDataExtra'] == 1) {
        $masteryCount++;
    } else {
        $completionCount++;
    }
}

foreach ($userAwards as &$userAward) {
    $userAward['AwardedAt'] = Carbon::createFromTimestampUTC($userAward['AwardedAt'])->toIso8601String();
    $userAward['AwardType'] = AwardType::toString((int) $userAward['AwardType']);

    // A user's hidden awards are not exposed to scrapers on their profile,
    // so we should not expose them via the API either.
    if ($userAward['DisplayOrder'] != '-1') {
        $onlyVisibleUserAwards[] = $userAward;
    }
}

$response = [
    "TotalAwardsCount" => count($userAwards),
    "HiddenAwardsCount" => count($userAwards) - count($onlyVisibleUserAwards),
    "MasteryAwardsCount" => $masteryCount,
    "CompletionAwardsCount" => $completionCount,
    "EventAwardsCount" => $eventsCount,
    "SiteAwardsCount" => $siteAwardsCount,
    "VisibleUserAwards" => $onlyVisibleUserAwards,
];

return response()->json($response, 200, [], JSON_UNESCAPED_SLASHES);
