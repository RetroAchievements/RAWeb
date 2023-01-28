<?php

declare(strict_types=1);

use LegacyApp\Community\Enums\AwardType;

function AddSiteAward(string $user, int $awardType, ?int $data = null, int $dataExtra = 0): void
{
    sanitize_sql_inputs($user);

    $displayOrder = 0;
    $query = "SELECT MAX( DisplayOrder ) FROM SiteAwards WHERE User = '$user'";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();
    } else {
        $dbData = mysqli_fetch_assoc($dbResult);
        if (isset($dbData['MAX( DisplayOrder )'])) {
            $displayOrder = (int) $dbData['MAX( DisplayOrder )'] + 1;
        }
    }

    $query = "INSERT INTO SiteAwards (AwardDate, User, AwardType, AwardData, AwardDataExtra, DisplayOrder)
                            VALUES( NOW(), '$user', '$awardType', '$data', '$dataExtra', '$displayOrder' ) ON DUPLICATE KEY UPDATE AwardDate = NOW()";
    $db = getMysqliConnection();
    mysqli_query($db, $query);
}

function HasSiteAward(string $user, int $awardType, int $data, ?int $dataExtra = null): bool
{
    sanitize_sql_inputs($user);
    $query = "SELECT AwardDate FROM SiteAwards WHERE User='$user' AND AwardType=$awardType AND AwardData=$data";
    if ($dataExtra !== null) {
        $query .= " AND AwardDataExtra=$dataExtra";
    }

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();

        return false;
    }

    $dbData = mysqli_fetch_assoc($dbResult);

    return isset($dbData['AwardDate']);
}

function getUsersSiteAwards(string $user, bool $showHidden = false): array
{
    sanitize_sql_inputs($user);

    $retVal = [];

    if (!isValidUsername($user)) {
        return $retVal;
    }

    $query = "
    (
    SELECT UNIX_TIMESTAMP( saw.AwardDate ) as AwardedAt, saw.AwardType, saw.AwardData, saw.AwardDataExtra, saw.DisplayOrder, gd.Title, c.Name AS ConsoleName, gd.Flags, gd.ImageIcon
                  FROM SiteAwards AS saw
                  LEFT JOIN GameData AS gd ON ( gd.ID = saw.AwardData AND saw.AwardType = " . AwardType::Mastery . " )
                  LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
                  WHERE saw.AwardType = " . AwardType::Mastery . " AND saw.User = '$user'
                  GROUP BY saw.AwardType, saw.AwardData, saw.AwardDataExtra
    )
    UNION
    (
    SELECT UNIX_TIMESTAMP(MAX( saw.AwardDate )) as AwardedAt, saw.AwardType, MAX( saw.AwardData ), saw.AwardDataExtra, saw.DisplayOrder, NULL, NULL, NULL, NULL
                  FROM SiteAwards AS saw
                  WHERE saw.AwardType > " . AwardType::Mastery . " AND saw.User = '$user'
                  GROUP BY saw.AwardType

    )
    ORDER BY DisplayOrder, AwardedAt, AwardType, AwardDataExtra ASC";

    $db = getMysqliConnection();
    $dbResult = mysqli_query($db, $query);

    $numFound = 0;
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[$numFound] = $db_entry;
            $numFound++;
        }

        // Updated way to "squash" duplicate awards to work with the new site award ordering implementation
        $completedGames = [];
        $masteredGames = [];

        // Get a separate list of completed and mastered games
        $retValCount = count($retVal);
        for ($i = 0; $i < $retValCount; $i++) {
            if ($retVal[$i]['AwardType'] == AwardType::Mastery &&
                $retVal[$i]['AwardDataExtra'] == 1) {
                $masteredGames[] = $retVal[$i]['AwardData'];
            } elseif ($retVal[$i]['AwardType'] == AwardType::Mastery &&
                $retVal[$i]['AwardDataExtra'] == 0) {
                $completedGames[] = $retVal[$i]['AwardData'];
            }
        }

        // Get a single list of games both completed and mastered
        if (!empty($completedGames) && !empty($masteredGames)) {
            $multiAwardGames = array_intersect($completedGames, $masteredGames);

            // For games that have been both completed and mastered, remove the completed entry from the award array.
            foreach ($multiAwardGames as $game) {
                $index = 0;
                foreach ($retVal as $award) {
                    if (isset($award['AwardData']) &&
                        $award['AwardData'] === $game &&
                        $award['AwardDataExtra'] == 0 &&
                        $award['AwardType'] == AwardType::Mastery) {
                        $retVal[$index] = "";
                        break;
                    }
                    $index++;
                }
            }
        }

        // Remove blank indexes
        $retVal = array_values(array_filter($retVal));
    }

    return $retVal;
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
function getRecentMasteryData(string $date, string $friendsOf = null, int $offset = 0, int $count = 50): array
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
