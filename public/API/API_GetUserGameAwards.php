<?php

/*
 *  API_GetUserGameAwards - gets user's completion and mastery awards
 *    u : username
 *
 *  array
 *   object     [value]
 *    string     AwardedAt                    iso 8601 date representing when the award was earned by the user
 *    string     GameID                       unique identifier of the game
 *    string     GameName                     name of the game
 *    string     ConsoleID                    unique identifier of the console associated to the game
 *    string     ConsoleName                  name of the console associated to the game
 *    bool       IsMastery                    true if the award was earned while in hardcore mode
 *    string     DisplayOrder                 used for determining which order to display the awards
 *    bool       IsMissingAchievementUnlocks  true if new achievements were added to the set after the award was earned
 */

use LegacyApp\Community\Enums\AwardType;

$user = request()->query('u');

$allUserSiteAwards = getUsersSiteAwards($user);

$filteredAwards = [];
foreach ($allUserSiteAwards as $userSiteAward) {
    $currentAwardType = (int) $userSiteAward['AwardType'];
    if (!AwardType::isActive($currentAwardType) || $currentAwardType !== AwardType::Mastery) {
        continue;
    }

    $gameID = (int) $userSiteAward['AwardData'];
    $gameData = [];

    getGameTitleFromID($gameID, $gameTitle, $consoleID, $consoleName, $forumTopicID, $gameData);

    // If we're unable to find the game, skip it.
    if (!$gameData || !isset($gameData['Title'])) {
        continue;
    }

    // "2014-09-29T12:41:48+00:00"
    $isoDateAwardedAt = (new DateTime("@${userSiteAward['AwardedAt']}"))->format(DateTime::ATOM);

    $award = [
        'AwardedAt' => $isoDateAwardedAt,
        'GameID' => $userSiteAward['AwardData'],
        'GameName' => $gameData['Title'],
        'ConsoleID' => $gameData['ConsoleID'],
        'ConsoleName' => $gameData['ConsoleName'],
        'IsMastery' => $userSiteAward['AwardDataExtra'] == '1',
        'DisplayOrder' => $userSiteAward['DisplayOrder'],
        'IsMissingAchievementUnlocks' => (isset($userSiteAward['Incomplete']) && $userSiteAward['Incomplete'] == 1),
    ];
    $filteredAwards[] = $award;
}

return response()->json($filteredAwards);
