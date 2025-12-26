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
    AwardType $awardType,
    ?int $data = null,
    int $dataExtra = 0,
    ?Carbon $awardDate = null,
    ?int $displayOrder = null,
): PlayerBadge {
    if (!isset($displayOrder)) {
        $displayOrder = 0;
        $maxDisplayOrder = PlayerBadge::where('user_id', $user->id)->max('order_column');
        if ($maxDisplayOrder) {
            $displayOrder = $maxDisplayOrder + 1;
        }
    }

    PlayerBadge::updateOrInsert(
        [
            'user_id' => $user->id,
            'award_type' => $awardType,
            'award_data' => $data,
            'award_data_extra' => $dataExtra,
        ],
        [
            'awarded_at' => $awardDate ?? Carbon::now(),
            'order_column' => $displayOrder,
        ]
    );

    return PlayerBadge::where('user_id', $user->id)
        ->where('award_type', $awardType)
        ->where('award_data', $data)
        ->where('award_data_extra', $dataExtra)
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

    $gameAwardValues = implode("','", AwardType::gameValues());

    $query = "
        -- game awards (mastery, beaten)
        SELECT " . unixTimestampStatement('saw.awarded_at', 'AwardedAt') . ", saw.award_type, saw.user_id, saw.award_data, saw.award_data_extra, saw.order_column, gd.Title, c.ID AS ConsoleID, c.Name AS ConsoleName, gd.Flags, gd.ImageIcon
            FROM user_awards AS saw
            LEFT JOIN GameData AS gd ON ( gd.ID = saw.award_data AND saw.award_type IN ('{$gameAwardValues}') )
            LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
            WHERE
                saw.award_type IN('{$gameAwardValues}')
                AND saw.user_id = :userId
            GROUP BY saw.award_type, saw.award_data, saw.award_data_extra
            HAVING
                -- Remove duplicate game beaten awards.
                (saw.award_type != '" . AwardType::GameBeaten->value . "' OR saw.award_data_extra = 1 OR NOT EXISTS (
                    SELECT 1 FROM user_awards AS saw2
                    WHERE saw2.award_type = saw.award_type AND saw2.award_data = saw.award_data AND saw2.award_data_extra = 1 AND saw2.user_id = saw.user_id
                ))
                -- Remove duplicate mastery awards.
                AND (saw.award_type != '" . AwardType::Mastery->value . "' OR saw.award_data_extra = 1 OR NOT EXISTS (
                    SELECT 1 FROM user_awards AS saw3
                    WHERE saw3.award_type = saw.award_type AND saw3.award_data = saw.award_data AND saw3.award_data_extra = 1 AND saw3.user_id = saw.user_id
                ))
        UNION
        -- event awards
        SELECT " . unixTimestampStatement('saw.awarded_at', 'AwardedAt') . ", saw.award_type, saw.user_id, saw.award_data, saw.award_data_extra, saw.order_column, gd.Title, " . System::Events . ", 'Events', NULL, e.image_asset_path
            FROM user_awards AS saw
            LEFT JOIN events e ON e.id = saw.award_data
            LEFT JOIN GameData gd ON gd.id = e.legacy_game_id
            WHERE
                saw.award_type = '" . AwardType::Event->value . "'
                AND saw.user_id = :userId3
        UNION
        -- non-game awards (developer contribution, ...)
        SELECT " . unixTimestampStatement('MAX(saw.awarded_at)', 'AwardedAt') . ", saw.award_type, saw.user_id, MAX( saw.award_data ), saw.award_data_extra, saw.order_column, NULL, NULL, NULL, NULL, NULL
            FROM user_awards AS saw
            WHERE
                saw.award_type NOT IN('{$gameAwardValues}','" . AwardType::Event->value . "')
                AND saw.user_id = :userId2
            GROUP BY saw.award_type
        ORDER BY order_column, AwardedAt, award_type, award_data_extra ASC";

    $dbResult = legacyDbFetchAll($query, $bindings)->toArray();

    foreach ($dbResult as &$award) {
        unset($award['user_id']);

        $award['AwardType'] = AwardType::from($award['award_type'])->toLegacyInteger();
        $award['AwardData'] = (int) $award['award_data'];
        $award['AwardDataExtra'] = (int) $award['award_data_extra'];
        $award['DisplayOrder'] = (int) $award['order_column'];

        if ($award['ConsoleID']) {
            settype($award['ConsoleID'], 'integer');
        }

        unset($award['award_type'], $award['award_data'], $award['award_data_extra'], $award['order_column']);
    }

    return $dbResult;
}

function HasPatreonBadge(User $user): bool
{
    return $user->playerBadges()
        ->where('award_type', AwardType::PatreonSupporter)
        ->exists();
}

function SetPatreonSupporter(User $user, bool $enable): void
{
    if ($enable) {
        $badge = AddSiteAward($user, AwardType::PatreonSupporter, 0, 0);
        SiteBadgeAwarded::dispatch($badge);
        // TODO PatreonSupporterAdded::dispatch($user);
    } else {
        $user->playerBadges()->where('award_type', AwardType::PatreonSupporter)->delete();
        // TODO PatreonSupporterRemoved::dispatch($user);
    }
}

function HasCertifiedLegendBadge(User $user): bool
{
    return $user->playerBadges()
        ->where('award_type', AwardType::CertifiedLegend)
        ->exists();
}

function SetCertifiedLegend(User $user, bool $enable): void
{
    if ($enable) {
        $badge = AddSiteAward($user, AwardType::CertifiedLegend, 0, 0);
        SiteBadgeAwarded::dispatch($badge);
    } else {
        $user->playerBadges()->where('award_type', AwardType::CertifiedLegend)->delete();
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
    ?AwardType $onlyAwardType = null,
    ?int $onlyUnlockMode = null,
): array {
    // Determine the friends condition
    $friendCondAward = "";
    if ($friendsOfUser) {
        $friendSubquery = GetFriendsSubquery($friendsOfUser->User, returnUserIds: true);
        $friendCondAward = "AND saw.user_id IN ($friendSubquery)";
    }

    $onlyAwardTypeClause = "
        WHERE saw.award_type IN ('" . AwardType::Mastery->value . "', '" . AwardType::GameBeaten->value . "')
    ";
    if ($onlyAwardType) {
        $onlyAwardTypeClause = "WHERE saw.award_type = '" . $onlyAwardType->value . "'";
    }

    $onlyUnlockModeClause = "saw.award_data_extra IS NOT NULL";
    if (isset($onlyUnlockMode)) {
        $onlyUnlockModeClause = "saw.award_data_extra = $onlyUnlockMode";
    }

    $retVal = [];
    $query = "SELECT ua.User, s.AwardedAt, s.AwardedAtUnix, s.award_type, s.award_data, s.award_data_extra, s.GameTitle, s.GameID, s.ConsoleName, s.GameIcon
        FROM (
            SELECT
                saw.user_id, saw.awarded_at as AwardedAt, UNIX_TIMESTAMP(saw.awarded_at) as AwardedAtUnix, saw.award_type,
                saw.award_data, saw.award_data_extra, gd.Title AS GameTitle, gd.ID AS GameID, c.Name AS ConsoleName, gd.ImageIcon AS GameIcon,
                ROW_NUMBER() OVER (PARTITION BY saw.user_id, saw.award_data, TIMESTAMPDIFF(MINUTE, saw.awarded_at, saw2.awarded_at) ORDER BY saw.award_type ASC) AS rn
            FROM user_awards AS saw
            LEFT JOIN GameData AS gd ON gd.ID = saw.award_data
            LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
            LEFT JOIN user_awards AS saw2 ON saw2.user_id = saw.user_id AND saw2.award_data = saw.award_data AND TIMESTAMPDIFF(MINUTE, saw.awarded_at, saw2.awarded_at) BETWEEN 0 AND 1
            $onlyAwardTypeClause AND saw.award_data > 0 AND $onlyUnlockModeClause $friendCondAward
            AND saw.awarded_at BETWEEN TIMESTAMP('$date') AND DATE_ADD('$date', INTERVAL 24 * 60 * 60 - 1 SECOND)
        ) s
        JOIN UserAccounts AS ua ON ua.ID = s.user_id
        WHERE s.rn = 1
        ORDER BY AwardedAt DESC
        LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $db_entry['AwardType'] = AwardType::from($db_entry['award_type'])->toLegacyInteger();
            $db_entry['AwardData'] = (int) $db_entry['award_data'];
            $db_entry['AwardDataExtra'] = (int) $db_entry['award_data_extra'];
            unset($db_entry['award_type'], $db_entry['award_data'], $db_entry['award_data_extra']);

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
        ->where('award_type', AwardType::Mastery)
        ->whereHas('gameIfApplicable.system', function ($query) {
            $query->where('ID', System::Events);
        })
        ->distinct('award_data')
        ->count('award_data');

    $eventBadgeCount = $user->playerBadges()
        ->where('award_type', AwardType::Event)
        ->distinct('award_data')
        ->count('award_data');

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
        ->where('award_data', $gameId)
        ->get();

    foreach ($foundAwards as $award) {
        $awardExtra = $award->award_data_extra;
        $awardType = $award->award_type;

        $key = '';
        if ($awardType === AwardType::Mastery) {
            $key = $awardExtra == UnlockMode::Softcore ? 'completed' : 'mastered';
        } elseif ($awardType === AwardType::GameBeaten) {
            $key = $awardExtra == UnlockMode::Softcore ? 'beaten-softcore' : 'beaten-hardcore';
        }

        if ($key && is_null($userGameProgressionAwards[$key])) {
            $userGameProgressionAwards[$key] = $award;
        }
    }

    return $userGameProgressionAwards;
}
