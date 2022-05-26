<?php

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
        $friendCondAward = "AND (saw.User IN (SELECT Friend FROM Friends WHERE User LIKE '$friendsOf' AND Friendship = 1) OR saw.User LIKE '$friendsOf')";
    }

    $retVal = [];
    $query = "SELECT saw.User, saw.AwardDate as AwardedAt, UNIX_TIMESTAMP( saw.AwardDate ) as AwardedAtUnix, saw.AwardType, saw.AwardData, saw.AwardDataExtra, gd.Title AS GameTitle, gd.ID AS GameID, c.Name AS ConsoleName, gd.ImageIcon AS GameIcon
                FROM SiteAwards AS saw
                LEFT JOIN GameData AS gd ON gd.ID = saw.AwardData
                LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
                WHERE saw.AwardType = 1 AND AwardData > 0 AND AwardDataExtra IS NOT NULL $friendCondAward
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
