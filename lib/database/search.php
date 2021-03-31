<?php

function performSearch($searchQuery, $offset, $count, &$searchResultsOut)
{
    global $db;

    sanitize_sql_inputs($searchQuery, $offset, $count);

    $resultCount = 0;

    $query = "
    (
        SELECT 'Game' AS Type, gd.ID, CONCAT( '/game/', gd.ID ) AS Target, CONCAT(gd.Title, ' (', c.Name, ')') as Title FROM GameData AS gd
        LEFT JOIN Achievements AS ach ON ach.GameID = gd.ID AND ach.Flags = 3
        LEFT JOIN Console AS c ON gd.ConsoleID = c.ID
        WHERE gd.Title LIKE '%$searchQuery%'
        GROUP BY gd.ID, gd.Title
    )
    UNION
    (
        SELECT 'Achievement' AS Type, ach.ID, CONCAT( '/achievement/', ach.ID ) AS Target, ach.Title FROM Achievements AS ach
        WHERE ach.Flags = 3 AND ach.Title LIKE '%$searchQuery%'
    )
    UNION
    (
        SELECT 'User' AS Type,
        ua.User AS ID,
        CONCAT( '/user/', ua.User ) AS Target,
        ua.User AS Title
        FROM UserAccounts AS ua
        WHERE ua.User LIKE '%$searchQuery%'
    )
    UNION
    (
        SELECT 'Forum Comment' AS Type,
        ua.User AS ID,
        CONCAT( '/viewtopic.php?t=', ftc.ForumTopicID, '&c=', ftc.ID ) AS Target,
        CONCAT( '...', MID( ftc.Payload, GREATEST( LOCATE('$searchQuery', ftc.Payload)-25, 1), 60 ), '...' ) AS Title
        FROM ForumTopicComment AS ftc
        LEFT JOIN UserAccounts AS ua ON ua.ID = ftc.AuthorID
        WHERE ftc.Payload LIKE '%$searchQuery%'
        GROUP BY ID, ftc.ID
    )
    UNION
    (
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

        CONCAT( '...', MID( c.Payload, GREATEST( LOCATE('$searchQuery', c.Payload)-40, 1), 60 ), '...' ) AS Title

        FROM Comment AS c
        LEFT JOIN UserAccounts AS ua ON ( ua.ID = c.ArticleID )
        LEFT JOIN UserAccounts AS cua ON cua.ID = c.UserID
        WHERE c.Payload LIKE '%$searchQuery%'
    )
    LIMIT $offset, $count
    ";

    $dbResult = mysqli_query($db, $query);

    if ($dbResult == false) {
        // error_log(__FUNCTION__ . " gone wrong!");
        log_sql_fail();
    } else {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $searchResultsOut[$resultCount] = $nextData;
            $resultCount++;
        }
    }

    return $resultCount;
}
