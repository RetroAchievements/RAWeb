<?php

use RA\ArticleType;
use RA\Permissions;
use RA\SearchType;

function canSearch(int $searchType, int $permissions): bool {
    switch ($searchType) {
        case SearchType::UserModerationComment:
        case SearchType::SetClaimComment:
            return $permissions >= Permissions::Admin;

        case SearchType::GameHashComment:
            return $permissions >= Permissions::Developer;

        case SearchType::TicketComment:
            // technically, just need to be logged in
            // but a not-logged-in user has Unregistered permissions.
            return $permissions >= Permissions::Registered;

        default:
            return true;
    }
}

function performSearch(int $searchType, string $searchQuery, int $offset, int $count,
    int $permissions, array &$searchResultsOut): int
{
    sanitize_sql_inputs($searchQuery, $offset, $count);

    if (!canSearch($searchType, $permissions)) {
        return 0;
    }

    $parts = [];
    if ($searchType == SearchType::Game || $searchType == SearchType::All) {
        $parts[] = "(
        SELECT " . SearchType::Game . " AS Type, gd.ID, CONCAT( '/game/', gd.ID ) AS Target, CONCAT(gd.Title, ' (', c.Name, ')') as Title FROM GameData AS gd
        LEFT JOIN Achievements AS ach ON ach.GameID = gd.ID AND ach.Flags = 3
        LEFT JOIN Console AS c ON gd.ConsoleID = c.ID
        WHERE gd.Title LIKE '%$searchQuery%'
        GROUP BY gd.ID, gd.Title
        ORDER BY gd.Title)";
    }

    if ($searchType == SearchType::Achievement || $searchType == SearchType::All) {
        $parts[] = "(
        SELECT " . SearchType::Achievement . " AS Type, ach.ID, CONCAT( '/achievement/', ach.ID ) AS Target, ach.Title FROM Achievements AS ach
        WHERE ach.Flags = 3 AND ach.Title LIKE '%$searchQuery%' ORDER BY ach.Title)";
    }

    if ($searchType == SearchType::User || $searchType == SearchType::All) {
        $parts[] = "(
        SELECT " . SearchType::User . " AS Type,
        ua.User AS ID,
        CONCAT( '/user/', ua.User ) AS Target,
        ua.User AS Title
        FROM UserAccounts AS ua
        WHERE ua.User LIKE '%$searchQuery%' AND ua.Permissions >= 0 AND ua.Deleted IS NULL
        ORDER BY ua.User)";
    }

    if ($searchType == SearchType::Forum || $searchType == SearchType::All) {
        $parts[] = "(
        SELECT " . SearchType::Forum . " AS Type,
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

    $articleTypes = [];

    if ($searchType == SearchType::GameComment || $searchType == SearchType::All) {
        $articleTypes[] = ArticleType::Game;
    }

    if ($searchType == SearchType::AchievementComment || $searchType == SearchType::All) {
        $articleTypes[] = ArticleType::Achievement;
    }

    if ($searchType == SearchType::LeaderboardComment || $searchType == SearchType::All) {
        $articleTypes[] = ArticleType::Leaderboard;
    }

    if ($searchType == SearchType::TicketComment || $searchType == SearchType::All) {
        if (canSearch(SearchType::GameHashComment, $permissions)) {
            $articleTypes[] = ArticleType::AchievementTicket;
        }
    }

    if ($searchType == SearchType::UserComment || $searchType == SearchType::All) {
        $articleTypes[] = ArticleType::User;
    }

    if ($searchType == SearchType::UserModerationComment || $searchType == SearchType::All) {
        if (canSearch(SearchType::UserModerationComment, $permissions)) {
            $articleTypes[] = ArticleType::UserModeration;
        }
    }

    if ($searchType == SearchType::GameHashComment || $searchType == SearchType::All) {
        if (canSearch(SearchType::GameHashComment, $permissions)) {
            $articleTypes[] = ArticleType::GameHash;
        }
    }

    if ($searchType == SearchType::SetClaimComment || $searchType == SearchType::All) {
        if (canSearch(SearchType::SetClaimComment, $permissions)) {
            $articleTypes[] = ArticleType::SetClaim;
        }
    }

    if (count($articleTypes) > 0) {
        $parts[] = "(
            SELECT CASE
                WHEN c.articletype=" . ArticleType::Game . " THEN " . SearchType::GameComment . "
                WHEN c.articletype=" . ArticleType::Achievement . " THEN " . SearchType::AchievementComment . "
                WHEN c.articletype=" . ArticleType::Leaderboard . " THEN " . SearchType::LeaderboardComment . "
                WHEN c.articletype=" . ArticleType::AchievementTicket . " THEN " . SearchType::TicketComment . "
                WHEN c.articletype=" . ArticleType::User . " THEN " . SearchType::UserComment . "
                WHEN c.articletype=" . ArticleType::UserModeration . " THEN " . SearchType::UserModerationComment . "
                WHEN c.articletype=" . ArticleType::GameHash . " THEN " . SearchType::GameHashComment . "
                WHEN c.articletype=" . ArticleType::SetClaim . " THEN " . SearchType::SetClaimComment . "
                ELSE 9999
            END AS Type,
            cua.User AS ID,
            CASE
                WHEN c.articletype=" . ArticleType::Game . " THEN CONCAT('/game/', c.ArticleID)
                WHEN c.articletype=" . ArticleType::Achievement . " THEN CONCAT('/achievement/', c.ArticleID)
                WHEN c.articletype=" . ArticleType::Leaderboard . " THEN CONCAT('/leaderboardinfo.php?i=', c.ArticleID)
                WHEN c.articletype=" . ArticleType::AchievementTicket . " THEN CONCAT('/ticketmanager.php?i=', c.ArticleID)
                WHEN c.articletype=" . ArticleType::User . " THEN CONCAT('/user/', ua.User)
                WHEN c.articletype=" . ArticleType::UserModeration . " THEN CONCAT('/user/', ua.User)
                WHEN c.articletype=" . ArticleType::GameHash . " THEN CONCAT('/managehashes.php?g=', c.ArticleID)
                WHEN c.articletype=" . ArticleType::SetClaim . " THEN CONCAT('/manageclaims.php?g=', c.ArticleID)
                ELSE CONCAT(c.articletype, '/', c.ArticleID)
            END AS Target,
            CASE
                WHEN CHAR_LENGTH(c.Payload) <= 64 THEN c.Payload
                ELSE CONCAT( '...', MID( c.Payload, GREATEST( LOCATE('$searchQuery', c.Payload)-25, 1), 60 ), '...' )
            END AS Title
            FROM Comment AS c
            LEFT JOIN UserAccounts AS cua ON cua.ID=c.UserID
            LEFT JOIN UserAccounts AS ua ON ua.ID=c.ArticleID AND c.articletype=" . ArticleType::User . "
            WHERE c.Payload LIKE '%$searchQuery%'
            AND cua.User != 'Server' AND c.articletype IN (" . implode(',', $articleTypes) . ")
            AND ua.Deleted IS NULL AND (ua.UserWallActive OR ua.UserWallActive IS NULL)
            ORDER BY c.articletype, c.Submitted DESC)";
    }

    $query = "SELECT SQL_CALC_FOUND_ROWS * FROM (" .
        implode(' UNION ALL ', $parts) . ") AS results ORDER BY Type LIMIT $offset, $count";

    $dbResult = s_mysql_sanitized_query($query);
    if (!$dbResult) {
        log_sql_fail();

        return 0;
    }

    $resultCount = 0;
    while ($nextData = mysqli_fetch_assoc($dbResult)) {
        $searchResultsOut[] = $nextData;
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
