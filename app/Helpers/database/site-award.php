<?php

use App\Community\Enums\AwardType;
use App\Models\PlayerBadge;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\UnlockMode;
use App\Platform\Events\SiteBadgeAwarded;
use Carbon\Carbon;

/**
 * @deprecated use PlayerBadge model
 */
function AddSiteAward(
    User $user,
    int $awardType,
    ?int $data = null,
    int $dataExtra = 0,
    ?Carbon $awardDate = null,
    ?int $displayOrder = null,
): PlayerBadge {
    if (!isset($displayOrder)) {
        $displayOrder = 0;
        $maxDisplayOrder = PlayerBadge::where('user_id', $user->id)->max('DisplayOrder');
        if ($maxDisplayOrder) {
            $displayOrder = $maxDisplayOrder + 1;
        }
    }

    PlayerBadge::updateOrInsert(
        [
            'User' => $user->User,
            'user_id' => $user->id,
            'AwardType' => $awardType,
            'AwardData' => $data,
            'AwardDataExtra' => $dataExtra,
        ],
        [
            'AwardDate' => $awardDate ?? Carbon::now(),
            'DisplayOrder' => $displayOrder,
        ]
    );

    return PlayerBadge::where('User', $user->User)
        ->where('AwardType', $awardType)
        ->where('AwardData', $data)
        ->where('AwardDataExtra', $dataExtra)
        ->first();
}

function removeDuplicateGameAwards(array &$dbResult, array $gamesToDedupe, int $awardType): void
{
    foreach ($gamesToDedupe as $game) {
        $index = 0;
        foreach ($dbResult as $award) {
            if (
                isset($award['AwardData'])
                && $award['AwardData'] === $game
                && $award['AwardDataExtra'] == UnlockMode::Softcore
                && $award['AwardType'] == $awardType
            ) {
                $dbResult[$index] = "";
                break;
            }

            $index++;
        }
    }
}

function getUsersSiteAwards(?User $user, bool $showHidden = false): array
{
    $dbResult = [];

    if (!$user) {
        return $dbResult;
    }

    $bindings = [
        'userId' => $user->id,
        'userId2' => $user->id,
    ];

    $query = "
        -- game awards (mastery, beaten)
        SELECT " . unixTimestampStatement('saw.AwardDate', 'AwardedAt') . ", saw.AwardType, saw.AwardData, saw.AwardDataExtra, saw.DisplayOrder, gd.Title, c.ID AS ConsoleID, c.Name AS ConsoleName, gd.Flags, gd.ImageIcon
            FROM SiteAwards AS saw
            LEFT JOIN GameData AS gd ON ( gd.ID = saw.AwardData AND saw.AwardType IN (" . implode(',', AwardType::game()) . ") )
            LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
            WHERE
                saw.AwardType IN(" . implode(',', AwardType::game()) . ")
                AND saw.user_id = :userId
            GROUP BY saw.AwardType, saw.AwardData, saw.AwardDataExtra
        UNION
        -- non-game awards (developer contribution, ...)
        SELECT " . unixTimestampStatement('MAX(saw.AwardDate)', 'AwardedAt') . ", saw.AwardType, MAX( saw.AwardData ), saw.AwardDataExtra, saw.DisplayOrder, NULL, NULL, NULL, NULL, NULL
            FROM SiteAwards AS saw
            WHERE
                saw.AwardType NOT IN(" . implode(',', AwardType::game()) . ")
                AND saw.user_id = :userId2
            GROUP BY saw.AwardType
        ORDER BY DisplayOrder, AwardedAt, AwardType, AwardDataExtra ASC";

    $dbResult = legacyDbFetchAll($query, $bindings)->toArray();

    // Updated way to "squash" duplicate awards to work with the new site award ordering implementation
    $softcoreBeatenGames = [];
    $hardcoreBeatenGames = [];
    $completedGames = [];
    $masteredGames = [];

    // Get a separate list of completed and mastered games
    $awardsCount = count($dbResult);
    for ($i = 0; $i < $awardsCount; $i++) {
        if ($dbResult[$i]['AwardType'] == AwardType::Mastery && $dbResult[$i]['AwardDataExtra'] == 1) {
            $masteredGames[] = $dbResult[$i]['AwardData'];
        } elseif ($dbResult[$i]['AwardType'] == AwardType::Mastery && $dbResult[$i]['AwardDataExtra'] == 0) {
            $completedGames[] = $dbResult[$i]['AwardData'];
        } elseif ($dbResult[$i]['AwardType'] == AwardType::GameBeaten && $dbResult[$i]['AwardDataExtra'] == 1) {
            $hardcoreBeatenGames[] = $dbResult[$i]['AwardData'];
        } elseif ($dbResult[$i]['AwardType'] == AwardType::GameBeaten && $dbResult[$i]['AwardDataExtra'] == 0) {
            $softcoreBeatenGames[] = $dbResult[$i]['AwardData'];
        }
    }

    // Get a single list of games both beaten hardcore and softcore
    if (!empty($hardcoreBeatenGames) && !empty($softcoreBeatenGames)) {
        $multiBeatenGames = array_intersect($hardcoreBeatenGames, $softcoreBeatenGames);
        removeDuplicateGameAwards($dbResult, $multiBeatenGames, AwardType::GameBeaten);
    }

    // Get a single list of games both completed and mastered
    if (!empty($completedGames) && !empty($masteredGames)) {
        $multiAwardGames = array_intersect($completedGames, $masteredGames);
        removeDuplicateGameAwards($dbResult, $multiAwardGames, AwardType::Mastery);
    }

    // Remove blank indexes
    $dbResult = array_values(array_filter($dbResult));

    foreach ($dbResult as &$award) {
        if ($award['ConsoleID']) {
            settype($award['AwardType'], 'integer');
            settype($award['AwardData'], 'integer');
            settype($award['AwardDataExtra'], 'integer');
            settype($award['ConsoleID'], 'integer');
        }
    }

    return $dbResult;
}

function HasPatreonBadge(User $user): bool
{
    return $user->playerBadges()
        ->where('AwardType', AwardType::PatreonSupporter)
        ->exists();
}

function SetPatreonSupporter(User $user, bool $enable): void
{
    if ($enable) {
        $badge = AddSiteAward($user, AwardType::PatreonSupporter, 0, 0);
        SiteBadgeAwarded::dispatch($badge);
        // TODO PatreonSupporterAdded::dispatch($user);
    } else {
        $user->playerBadges()->where('AwardType', AwardType::PatreonSupporter)->delete();
        // TODO PatreonSupporterRemoved::dispatch($user);
    }
}

function HasCertifiedLegendBadge(User $user): bool
{
    return $user->playerBadges()
        ->where('AwardType', AwardType::CertifiedLegend)
        ->exists();
}

function SetCertifiedLegend(User $user, bool $enable): void
{
    if ($enable) {
        $badge = AddSiteAward($user, AwardType::CertifiedLegend, 0, 0);
        SiteBadgeAwarded::dispatch($badge);
    } else {
        $user->playerBadges()->where('AwardType', AwardType::CertifiedLegend)->delete();
    }
}

/**
 * Gets completed and mastery award information.
 * This includes User, Game and Completed or Mastered Date.
 *
 * Results are configurable based on input parameters allowing returning data for a specific users friends
 * and selecting a specific date
 */
function getRecentProgressionAwardData(
    string $date,
    ?User $friendsOfUser = null,
    int $offset = 0,
    int $count = 50,
    ?int $onlyAwardType = null,
    ?int $onlyUnlockMode = null,
): array {
    $startOfDay = Carbon::createFromFormat("Y-m-d", $date)->startOfDay();
    $endOfDay = Carbon::createFromFormat("Y-m-d", $date)->endOfDay();

    $initialResults = PlayerBadge::with(['user', 'gameIfApplicable.system'])
        ->whereBetween('AwardDate', [$startOfDay, $endOfDay])
        ->when($onlyAwardType !== null, fn ($query) => $query->where('AwardType', $onlyAwardType))
        ->when($onlyUnlockMode !== null, fn ($query) => $query->where('AwardDataExtra', $onlyUnlockMode))
        ->when($friendsOfUser !== null, fn ($query) => $query->whereIn('user_id', $friendsOfUser->followedUsers()->pluck('UserAccounts.id')->toArray())
        )
        ->orderBy('AwardDate', 'DESC')
        ->offset($offset)

        // We don't want to show multiple rows per user for the same game if they earned
        // multiple awards very quickly (ie: beat a game and also mastered it simultaneously).
        // Unfortunately, using window functions like ROW_NUMBER() with Eloquent ORM is
        // a huge hassle and arguably makes this code much more difficult to maintain. Rather than
        // try to use a window function, we'll slightly overfetch from the database, then manually
        // filter the records down later.
        ->limit($count * 2)

        ->get();

    $filteredResults = $initialResults->reduce(function ($carry, $item) {
        if (is_null($carry)) {
            $carry = collect([$item]);
        } else {
            $last = $carry->last();
            $lastUser = $last?->user;
            $itemUser = $item?->user;

            if (
                $lastUser === null
                || $itemUser === null
                || !$lastUser->is($itemUser)
                || $last->AwardData !== $item->AwardData
                || $last->AwardDate->diffInMinutes($item->AwardDate) >= 15
            ) {
                $carry->push($item);
            }
        }

        return $carry;
    }, collect())->take($count);

    // Now that we've queried and filtered, we'll perform a final mapping and return.
    return $filteredResults->map(function ($badge) {
        return [
            'User' => $badge->user->User,
            'AwardedAt' => $badge->AwardDate->format('Y-m-d H:i:s'),
            'AwardType' => $badge->AwardType,
            'AwardData' => $badge->AwardData,
            'AwardDataExtra' => $badge->AwardDataExtra,
            'GameTitle' => $badge->game?->title,
            'GameID' => $badge->game?->id,
            'ConsoleName' => $badge->game?->system->name,
            'GameIcon' => $badge->game?->ImageIcon,
        ];
    })->toArray();
}

/**
 * Gets the number of event awards a user has earned
 */
function getUserEventAwardCount(User $user): int
{
    return $user->playerBadges()
        ->where('AwardType', AwardType::Mastery)
        ->whereHas('gameIfApplicable.system', function ($query) {
            $query->where('ID', System::Events);
        })
        ->distinct('AwardData')
        ->count('AwardData');
}

/**
 * Retrieves a target user's site award metadata for a given game ID.
 * An array is returned with keys "beaten-softcore", "beaten-hardcore",
 * "completed", and "mastered", which contain corresponding award details.
 * If no progression awards are found, or if the target username is not provided,
 * no awards are fetched or returned.
 *
 * @return array the array of a target user's site award metadata for a given game ID
 */
function getUserGameProgressionAwards(int $gameId, string $username): array
{
    $userGameProgressionAwards = [
        'beaten-softcore' => null,
        'beaten-hardcore' => null,
        'completed' => null,
        'mastered' => null,
    ];

    $foundAwards = PlayerBadge::where('User', '=', $username)
        ->where('AwardData', '=', $gameId)
        ->get();

    foreach ($foundAwards as $award) {
        $awardExtra = $award['AwardDataExtra'];
        $awardType = $award->AwardType;

        $key = '';
        if ($awardType == AwardType::Mastery) {
            $key = $awardExtra == UnlockMode::Softcore ? 'completed' : 'mastered';
        } elseif ($awardType == AwardType::GameBeaten) {
            $key = $awardExtra == UnlockMode::Softcore ? 'beaten-softcore' : 'beaten-hardcore';
        }

        if ($key && is_null($userGameProgressionAwards[$key])) {
            $userGameProgressionAwards[$key] = $award;
        }
    }

    return $userGameProgressionAwards;
}
