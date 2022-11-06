<?php

use RA\SearchType;

function performSearch(int $searchType, string $searchQuery, int $offset, int $count,
    int $permissions, array &$searchResultsOut): int
{
    sanitize_sql_inputs($searchQuery, $offset, $count);

    $parts = [];
    if ($searchType == SearchType::Game || $searchType == SearchType::All) {
        $parts[] = "(
        SELECT 'Game' AS Type, gd.ID, CONCAT( '/game/', gd.ID ) AS Target, CONCAT(gd.Title, ' (', c.Name, ')') as Title FROM GameData AS gd
        LEFT JOIN Achievements AS ach ON ach.GameID = gd.ID AND ach.Flags = 3
        LEFT JOIN Console AS c ON gd.ConsoleID = c.ID
        WHERE gd.Title LIKE '%$searchQuery%'
        GROUP BY gd.ID, gd.Title
        ORDER BY gd.Title)";
    }

    if ($searchType == SearchType::Achievement || $searchType == SearchType::All) {
        $parts[] = "(
        SELECT 'Achievement' AS Type, ach.ID, CONCAT( '/achievement/', ach.ID ) AS Target, ach.Title FROM Achievements AS ach
        WHERE ach.Flags = 3 AND ach.Title LIKE '%$searchQuery%' ORDER BY ach.Title)";
    }

    if ($searchType == SearchType::User || $searchType == SearchType::All) {
        $parts[] = "(
        SELECT 'User' AS Type,
        ua.User AS ID,
        CONCAT( '/user/', ua.User ) AS Target,
        ua.User AS Title
        FROM UserAccounts AS ua
        WHERE ua.User LIKE '%$searchQuery%' AND ua.Permissions >= 0 AND ua.Deleted IS NULL
        ORDER BY ua.User)";
    }

    if ($searchType == SearchType::Forum || $searchType == SearchType::All) {
        $parts[] = "(
        SELECT 'Forum Comment' AS Type,
        ua.User AS ID,
        CONCAT( '/viewtopic.php?t=', ftc.ForumTopicID, '&c=', ftc.ID, '#', ftc.ID ) AS Target,
        CASE WHEN CHAR_LENGTH(ftc.Payload) <= 64 THEN ftc.Payload ELSE
        CONCAT( '...', MID( ftc.Payload, GREATEST( LOCATE('$searchQuery', ftc.Payload)-25, 1), 60 ), '...' ) END AS Title
        FROM ForumTopicComment AS ftc
        LEFT JOIN UserAccounts AS ua ON ua.ID = ftc.AuthorID
        LEFT JOIN ForumTopic AS ft ON ft.ID = ftc.ForumTopicID
        WHERE ftc.Payload LIKE '%$searchQuery%'
        AND ft.RequiredPermissions <= '$permissions'
        GROUP BY ID, ftc.ID
        ORDER BY DateModified DESC)";
    }

    if ($searchType == SearchType::Comment || $searchType == SearchType::All) {
        $parts[] = "(
        SELECT 'Comment' AS Type, cua.User AS ID,

        CASE
            WHEN c.articletype=1 THEN CONCAT( '/game/', c.ArticleID )
            WHEN c.articletype=2 THEN CONCAT( '/achievement/', c.ArticleID )
            WHEN c.articletype=3 THEN CONCAT( '/user/', ua.User )
            WHEN c.articletype=5 THEN CONCAT( '/feed.php?a=', c.ArticleID )
            WHEN c.articletype=7 THEN CONCAT( '/ticketmanager.php?i=', c.ArticleID )
            ELSE CONCAT( c.articletype, '/', c.ArticleID )
        END
        AS Target,

        CASE WHEN CHAR_LENGTH(c.Payload) <= 64 THEN c.Payload ELSE
        CONCAT( '...', MID( c.Payload, GREATEST( LOCATE('$searchQuery', c.Payload)-25, 1), 60 ), '...' ) END AS Title

        FROM Comment AS c
        LEFT JOIN UserAccounts AS ua ON ( ua.ID = c.ArticleID )
        LEFT JOIN UserAccounts AS cua ON cua.ID = c.UserID
        WHERE c.Payload LIKE '%$searchQuery%'
        AND cua.User != 'Server'
        AND ua.UserWallActive AND ua.Deleted IS NULL
        AND c.articletype IN (1,2,3,5,7)
        ORDER BY c.Submitted DESC)";
    }

    $query = implode(' UNION ALL ', $parts) . " LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();

        return 0;
    }

    $resultCount = 0;
    while ($nextData = mysqli_fetch_assoc($dbResult)) {
        $searchResultsOut[$resultCount] = $nextData;
        $resultCount++;
    }

    if ($offset != 0 || $resultCount >= $count) {
        $query = "SELECT FOUND_ROWS() AS NumResults";
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            $resultCount = mysqli_fetch_assoc($dbResult)['NumResults'];
        }
    }

    return $resultCount;
}
