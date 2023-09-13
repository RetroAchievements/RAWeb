<?php

use App\Community\Enums\AwardType;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\PlayerBadge;
use Carbon\Carbon;

function AddSiteAward(
    string $user,
    int $awardType,
    ?int $data = null,
    int $dataExtra = 0,
    ?Carbon $awardDate = null,
    ?int $displayOrder = null,
): void {
    if (!isset($displayOrder)) {
        $displayOrder = 0;
        $query = "SELECT MAX(DisplayOrder) AS MaxDisplayOrder FROM SiteAwards WHERE User = :user";
        $dbData = legacyDbFetch($query, ['user' => $user]);
        if (isset($dbData['MaxDisplayOrder'])) {
            $displayOrder = (int) $dbData['MaxDisplayOrder'] + 1;
        }
    }

    $award = PlayerBadge::firstOrNew([
        'User' => $user,
        'AwardType' => $awardType,
        'AwardData' => $data,
        'AwardDataExtra' => $dataExtra,
    ], [
        'DisplayOrder' => $displayOrder,
    ]);

    $award->AwardDate = $awardDate ?? Carbon::now();
    $award->save();
}

function HasBeatenSiteAwards(string $username, int $gameId): bool
{
    return PlayerBadge::where('User', $username)
        ->where('AwardType', AwardType::GameBeaten)
        ->where('AwardData', $gameId)
        ->count() > 0;
}

function HasSiteAward(string $user, int $awardType, int $data, ?int $dataExtra = null): bool
{
    $query = "SELECT AwardDate FROM SiteAwards WHERE User=:user AND AwardType=$awardType AND AwardData=$data";
    if ($dataExtra !== null) {
        $query .= " AND AwardDataExtra=$dataExtra";
    }

    $dbData = legacyDbFetch($query, ['user' => $user]);

    return isset($dbData['AwardDate']);
}

function getUsersWithAward(int $awardType, int $data, ?int $dataExtra = null): array
{
    $query = "SELECT u.User, u.EmailAddress FROM SiteAwards saw
              LEFT JOIN UserAccounts u ON u.User=saw.User
              WHERE saw.AwardType=$awardType AND saw.AwardData=$data";
    if ($dataExtra != null) {
        $query .= " AND saw.AwardDataExtra=$dataExtra";
    }

    return legacyDbFetchAll($query)->toArray();
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

function getUsersSiteAwards(string $user, bool $showHidden = false): array
{
    $dbResult = [];

    if (!isValidUsername($user)) {
        return $dbResult;
    }

    $bindings = [
        'username' => $user,
        'username2' => $user,
    ];

    $query = "
    SELECT " . unixTimestampStatement('saw.AwardDate', 'AwardedAt') . ", saw.AwardType, saw.AwardData, saw.AwardDataExtra, saw.DisplayOrder, gd.Title, c.ID AS ConsoleID, c.Name AS ConsoleName, gd.Flags, gd.ImageIcon
                  FROM SiteAwards AS saw
                  LEFT JOIN GameData AS gd ON ( gd.ID = saw.AwardData AND (saw.AwardType = " . AwardType::Mastery . " OR saw.AwardType = " . AwardType::GameBeaten . ") )
                  LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
                  WHERE (saw.AwardType = " . AwardType::Mastery . " OR saw.AwardType = " . AwardType::GameBeaten . ") AND saw.User = :username
                  GROUP BY saw.AwardType, saw.AwardData, saw.AwardDataExtra
    UNION
    SELECT " . unixTimestampStatement('MAX(saw.AwardDate)', 'AwardedAt') . ", saw.AwardType, MAX( saw.AwardData ), saw.AwardDataExtra, saw.DisplayOrder, NULL, NULL, NULL, NULL, NULL
                  FROM SiteAwards AS saw
                  WHERE saw.AwardType > " . AwardType::Mastery . " AND saw.User = :username2
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

function HasPatreonBadge(string $username): bool
{
    sanitize_sql_inputs($username);

    $query = "SELECT * FROM SiteAwards AS sa "
        . "WHERE sa.AwardType = " . AwardType::PatreonSupporter . " AND sa.User = '$username'";

    $dbResult = s_mysql_query($query);

    return mysqli_num_rows($dbResult) > 0;
}

function SetPatreonSupporter(string $username, bool $enable): void
{
    sanitize_sql_inputs($username);

    if ($enable) {
        AddSiteAward($username, AwardType::PatreonSupporter, 0, 0);
    } else {
        $query = "DELETE FROM SiteAwards WHERE User = '$username' AND AwardType = " . AwardType::PatreonSupporter;
        s_mysql_query($query);
    }
}

function HasCertifiedLegendBadge(string $username): bool
{
    sanitize_sql_inputs($username);

    $query = "SELECT * FROM SiteAwards AS sa "
        . "WHERE sa.AwardType = " . AwardType::CertifiedLegend . " AND sa.User = '$username'";

    $dbResult = s_mysql_query($query);

    return mysqli_num_rows($dbResult) > 0;
}

function SetCertifiedLegend(string $usernameIn, bool $enable): void
{
    sanitize_sql_inputs($usernameIn);

    if ($enable) {
        AddSiteAward($usernameIn, AwardType::CertifiedLegend, 0, 0);
    } else {
        $query = "DELETE FROM SiteAwards WHERE User = '$usernameIn' AND AwardType = " . AwardType::CertifiedLegend;
        s_mysql_query($query);
    }
}

/**
 * Gets completed and mastery award information.
 * This includes User, Game and Completed or Mastered Date.
 *
 * Results are configurable based on input parameters allowing returning data for a specific users friends
 * and selecting a specific date
 */
function getRecentMasteryData(string $date, ?string $friendsOf = null, int $offset = 0, int $count = 50): array
{
    // Determine the friends condition
    $friendCondAward = "";
    if ($friendsOf !== null) {
        $friendSubquery = GetFriendsSubquery($friendsOf);
        $friendCondAward = "AND saw.User IN ($friendSubquery)";
    }

    $retVal = [];
    $query = "SELECT saw.User, saw.AwardDate as AwardedAt, UNIX_TIMESTAMP( saw.AwardDate ) as AwardedAtUnix, saw.AwardType, saw.AwardData, saw.AwardDataExtra, gd.Title AS GameTitle, gd.ID AS GameID, c.Name AS ConsoleName, gd.ImageIcon AS GameIcon
                FROM SiteAwards AS saw
                LEFT JOIN GameData AS gd ON gd.ID = saw.AwardData
                LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
                WHERE saw.AwardType = " . AwardType::Mastery . " AND AwardData > 0 AND AwardDataExtra IS NOT NULL $friendCondAward
                AND saw.AwardDate BETWEEN TIMESTAMP('$date') AND DATE_ADD('$date', INTERVAL 24 * 60 * 60 - 1 SECOND)
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
function getUserEventAwardCount(string $user): int
{
    $bindings = [
        'user' => $user,
        'type' => AwardType::Mastery,
        'event' => 101,
    ];

    $query = "SELECT COUNT(DISTINCT AwardData) AS TotalAwards 
              FROM SiteAwards sa
              INNER JOIN GameData gd ON gd.ID = sa.AwardData
              WHERE User = :user              
              AND AwardType = :type
              AND gd.ConsoleID = :event";

    $dataOut = legacyDbFetch($query, $bindings);

    return $dataOut['TotalAwards'];
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
