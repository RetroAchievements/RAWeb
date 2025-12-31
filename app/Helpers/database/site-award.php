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

    return PlayerBadge::where('user_id', $user->id)
        ->where('AwardType', $awardType)
        ->where('AwardData', $data)
        ->where('AwardDataExtra', $dataExtra)
        ->first();
}

function getUsersSiteAwards(?User $user): array
{
    $dbResult = [];

    if (!$user) {
        return $dbResult;
    }

    $bindings = [
        'userId' => $user->id,
        'userId2' => $user->id,
        'userId3' => $user->id,
    ];

    $query = "
        -- game awards (mastery, beaten)
        SELECT " . unixTimestampStatement('saw.AwardDate', 'AwardedAt') . ", saw.AwardType, saw.user_id, saw.AwardData, saw.AwardDataExtra, saw.DisplayOrder, gd.title AS Title, s.id AS ConsoleID, s.name AS ConsoleName, NULL AS Flags, gd.image_icon_asset_path AS ImageIcon
            FROM SiteAwards AS saw
            LEFT JOIN games AS gd ON ( gd.id = saw.AwardData AND saw.AwardType IN (" . implode(',', AwardType::game()) . ") )
            LEFT JOIN systems AS s ON s.id = gd.system_id
            WHERE
                saw.AwardType IN(" . implode(',', AwardType::game()) . ")
                AND saw.user_id = :userId
            GROUP BY saw.AwardType, saw.AwardData, saw.AwardDataExtra
            HAVING
                -- Remove duplicate game beaten awards
                (saw.AwardType != " . AwardType::GameBeaten . " OR saw.AwardDataExtra = 1 OR NOT EXISTS (
                    SELECT 1 FROM SiteAwards AS saw2
                    WHERE saw2.AwardType = saw.AwardType AND saw2.AwardData = saw.AwardData AND saw2.AwardDataExtra = 1 AND saw2.user_id = saw.user_id
                ))
                -- Remove duplicate mastery awards
                AND (saw.AwardType != " . AwardType::Mastery . " OR saw.AwardDataExtra = 1 OR NOT EXISTS (
                    SELECT 1 FROM SiteAwards AS saw3
                    WHERE saw3.AwardType = saw.AwardType AND saw3.AwardData = saw.AwardData AND saw3.AwardDataExtra = 1 AND saw3.user_id = saw.user_id
                ))
        UNION
        -- event awards
        SELECT " . unixTimestampStatement('saw.AwardDate', 'AwardedAt') . ", saw.AwardType, saw.user_id, saw.AwardData, saw.AwardDataExtra, saw.DisplayOrder, gd.title AS Title, " . System::Events . ", 'Events', NULL, e.image_asset_path AS ImageIcon
            FROM SiteAwards AS saw
            LEFT JOIN events e ON e.id = saw.AwardData
            LEFT JOIN games gd ON gd.id = e.legacy_game_id
            WHERE
                saw.AwardType = " . AwardType::Event . "
                AND saw.user_id = :userId3
        UNION
        -- non-game awards (developer contribution, ...)
        SELECT " . unixTimestampStatement('MAX(saw.AwardDate)', 'AwardedAt') . ", saw.AwardType, saw.user_id, MAX( saw.AwardData ), saw.AwardDataExtra, saw.DisplayOrder, NULL, NULL, NULL, NULL, NULL
            FROM SiteAwards AS saw
            WHERE
                saw.AwardType NOT IN(" . implode(',', AwardType::game()) . "," . AwardType::Event . ")
                AND saw.user_id = :userId2
            GROUP BY saw.AwardType
        ORDER BY DisplayOrder, AwardedAt, AwardType, AwardDataExtra ASC";

    $dbResult = legacyDbFetchAll($query, $bindings)->toArray();

    foreach ($dbResult as &$award) {
        unset($award['user_id']);

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
    // Determine the friends condition
    $friendCondAward = "";
    if ($friendsOfUser) {
        $friendSubquery = GetFriendsSubquery($friendsOfUser->username, returnUserIds: true);
        $friendCondAward = "AND saw.user_id IN ($friendSubquery)";
    }

    $onlyAwardTypeClause = "
        WHERE saw.AwardType IN (" . AwardType::Mastery . ", " . AwardType::GameBeaten . ")
    ";
    if ($onlyAwardType) {
        $onlyAwardTypeClause = "WHERE saw.AwardType = $onlyAwardType";
    }

    $onlyUnlockModeClause = "saw.AwardDataExtra IS NOT NULL";
    if (isset($onlyUnlockMode)) {
        $onlyUnlockModeClause = "saw.AwardDataExtra = $onlyUnlockMode";
    }

    $retVal = [];
    $query = "SELECT ua.username AS User, sub.AwardedAt, sub.AwardedAtUnix, sub.AwardType, sub.AwardData, sub.AwardDataExtra, sub.GameTitle, sub.GameID, sub.ConsoleName, sub.GameIcon
        FROM (
            SELECT
                saw.user_id, saw.AwardDate as AwardedAt, UNIX_TIMESTAMP(saw.AwardDate) as AwardedAtUnix, saw.AwardType,
                saw.AwardData, saw.AwardDataExtra, gd.title AS GameTitle, gd.id AS GameID, s.name AS ConsoleName, gd.image_icon_asset_path AS GameIcon,
                ROW_NUMBER() OVER (PARTITION BY saw.user_id, saw.AwardData, TIMESTAMPDIFF(MINUTE, saw.AwardDate, saw2.AwardDate) ORDER BY saw.AwardType ASC) AS rn
            FROM SiteAwards AS saw
            LEFT JOIN games AS gd ON gd.id = saw.AwardData
            LEFT JOIN systems AS s ON s.id = gd.system_id
            LEFT JOIN SiteAwards AS saw2 ON saw2.user_id = saw.user_id AND saw2.AwardData = saw.AwardData AND TIMESTAMPDIFF(MINUTE, saw.AwardDate, saw2.AwardDate) BETWEEN 0 AND 1
            $onlyAwardTypeClause AND saw.AwardData > 0 AND $onlyUnlockModeClause $friendCondAward
            AND saw.AwardDate BETWEEN TIMESTAMP('$date') AND DATE_ADD('$date', INTERVAL 24 * 60 * 60 - 1 SECOND)
        ) sub
        JOIN users AS ua ON ua.id = sub.user_id
        WHERE sub.rn = 1
        ORDER BY AwardedAt DESC
        LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }

    return $retVal;
}

/**
 * Gets the number of event awards a user has earned
 */
function getUserEventAwardCount(User $user): int
{
    $eventGameBadgeCount = $user->playerBadges()
        ->where('AwardType', AwardType::Mastery)
        ->whereHas('gameIfApplicable.system', function ($query) {
            $query->where('id', System::Events);
        })
        ->distinct('AwardData')
        ->count('AwardData');

    $eventBadgeCount = $user->playerBadges()
        ->where('AwardType', AwardType::Event)
        ->distinct('AwardData')
        ->count('AwardData');

    return $eventGameBadgeCount + $eventBadgeCount;
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
function getUserGameProgressionAwards(int $gameId, User $user): array
{
    $userGameProgressionAwards = [
        'beaten-softcore' => null,
        'beaten-hardcore' => null,
        'completed' => null,
        'mastered' => null,
    ];

    $foundAwards = PlayerBadge::where('user_id', $user->id)
        ->where('AwardData', $gameId)
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
