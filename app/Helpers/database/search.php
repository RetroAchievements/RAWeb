<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Enums\SearchType;
use App\Platform\Enums\GameSetType;

function canSearch(int $searchType, int $permissions): bool
{
    return match ($searchType) {
        SearchType::UserModerationComment, SearchType::SetClaimComment => $permissions >= Permissions::Moderator,
        SearchType::GameHashComment => $permissions >= Permissions::Developer,
        // technically, just need to be logged in
        // but a not-logged-in user has Unregistered permissions.
        SearchType::TicketComment => $permissions >= Permissions::Registered,
        default => true,
    };
}

function performSearch(
    int|array $searchType,
    string $searchQuery,
    int $offset,
    int $count,
    int $permissions,
    ?array &$searchResultsOut,
    bool $wantTotalResults = true
): int {
    sanitize_sql_inputs($searchQuery, $offset, $count);

    if (is_int($searchType)) {
        if ($searchType == SearchType::All) {
            $searchType = array_filter(SearchType::cases(), fn ($c) => $c != SearchType::All);
        } else {
            $searchType = [$searchType];
        }
    }

    $counts = [];
    $parts = [];

    if (in_array(SearchType::Game, $searchType)) {
        $counts[] = "SELECT COUNT(*) AS Count FROM GameData WHERE Title LIKE '%$searchQuery%'";
        $parts[] = "
        SELECT " . SearchType::Game . " AS Type, gd.ID, CONCAT( '/game/', gd.ID ) AS Target,
               CONCAT(gd.Title, ' (', c.Name, ')') AS Title,
               CASE
                   WHEN gd.Title LIKE '$searchQuery%' THEN 0
                   WHEN gd.Title LIKE '%~ $searchQuery%' THEN 1
                   ELSE 2
               END AS SecondarySort
        FROM GameData AS gd
        LEFT JOIN Achievements AS ach ON ach.GameID = gd.ID AND ach.Flags = 3
        LEFT JOIN Console AS c ON gd.ConsoleID = c.ID
        WHERE gd.ConsoleID != 100
        AND gd.Title LIKE '%$searchQuery%'
        GROUP BY gd.ID, gd.Title
        ORDER BY SecondarySort, REPLACE(gd.Title, '|', ''), gd.Title";
    }

    if (in_array(SearchType::Hub, $searchType)) {
        $counts[] = "SELECT COUNT(*) AS Count FROM game_sets WHERE deleted_at IS NULL AND type = '" . GameSetType::Hub->value . "' AND title LIKE '%$searchQuery%'";
        $parts[] = "
        SELECT " . SearchType::Hub . " AS Type, gs.id AS ID, CONCAT('/hub/', gs.id) AS Target,
               CONCAT(gs.title, ' (Hub)') AS Title,
               CASE
                   WHEN gs.title LIKE '$searchQuery%' THEN 0
                   WHEN gs.title LIKE '%~ $searchQuery%' THEN 1
                   ELSE 2
               END AS SecondarySort
        FROM game_sets AS gs
        WHERE gs.deleted_at IS NULL 
        AND gs.type = '" . GameSetType::Hub->value . "'
        AND gs.title LIKE '%$searchQuery%'
        GROUP BY gs.id, gs.title
        ORDER BY SecondarySort, REPLACE(gs.title, '|', ''), gs.title";
    }

    if (in_array(SearchType::Achievement, $searchType)) {
        $counts[] = "SELECT COUNT(*) AS Count FROM Achievements WHERE Title LIKE '%$searchQuery%'";
        $parts[] = "
        SELECT " . SearchType::Achievement . " AS Type, ach.ID, CONCAT( '/achievement/', ach.ID ) AS Target,
               CONCAT(ach.Title, ' (', gd.Title, ')') AS Title,
               CASE WHEN ach.Title LIKE '$searchQuery%' THEN 0 ELSE 1 END AS SecondarySort
        FROM Achievements AS ach
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        WHERE ach.Flags = 3 AND ach.Title LIKE '%$searchQuery%'
        ORDER BY SecondarySort, ach.Title";
    }

    if (in_array(SearchType::User, $searchType)) {
        $counts[] = "SELECT COUNT(*) AS Count FROM UserAccounts WHERE display_name LIKE '%$searchQuery%'";
        $parts[] = "
            SELECT " . SearchType::User . " AS Type, ua.display_name AS ID,
                CONCAT( '/user/', ua.display_name ) AS Target, ua.display_name AS Title,
                CASE WHEN ua.display_name LIKE '$searchQuery%' THEN 0 ELSE 1 END AS SecondarySort
            FROM UserAccounts AS ua
            WHERE ua.display_name LIKE '%$searchQuery%' AND ua.Permissions >= 0 AND ua.Deleted IS NULL
            ORDER BY SecondarySort, ua.display_name";
    }

    if (in_array(SearchType::Forum, $searchType)) {
        $counts[] = "SELECT COUNT(*) AS Count FROM forum_topic_comments WHERE body LIKE '%$searchQuery%'";
        $parts[] = "
        SELECT " . SearchType::Forum . " AS Type, ua.User AS ID,
               CONCAT( '/forums/topic/', ftc.forum_topic_id, '?comment=', ftc.id, '#', ftc.id ) AS Target,
               CASE WHEN CHAR_LENGTH(ftc.body) <= 64 THEN ftc.body ELSE
               CONCAT( '...', MID( ftc.body, GREATEST( LOCATE('$searchQuery', ftc.body)-25, 1), 60 ), '...' ) END AS Title
        FROM forum_topic_comments AS ftc
        LEFT JOIN UserAccounts AS ua ON ua.ID = ftc.author_id
        LEFT JOIN forum_topics AS ft ON ft.id = ftc.forum_topic_id
        WHERE ftc.body LIKE '%$searchQuery%' AND ft.deleted_at IS NULL
        AND ft.required_permissions <= '$permissions'
        GROUP BY ID, ftc.id
        ORDER BY IFNULL(ftc.updated_at, ftc.created_at) DESC";
    }

    $articleTypes = [];

    if (in_array(SearchType::GameComment, $searchType)) {
        $articleTypes[] = ArticleType::Game;
    }

    if (in_array(SearchType::AchievementComment, $searchType)) {
        $articleTypes[] = ArticleType::Achievement;
    }

    if (in_array(SearchType::LeaderboardComment, $searchType)) {
        $articleTypes[] = ArticleType::Leaderboard;
    }

    if (in_array(SearchType::TicketComment, $searchType)) {
        if (canSearch(SearchType::GameHashComment, $permissions)) {
            $articleTypes[] = ArticleType::AchievementTicket;
        }
    }

    if (in_array(SearchType::UserComment, $searchType)) {
        $articleTypes[] = ArticleType::User;
    }

    if (in_array(SearchType::UserModerationComment, $searchType)) {
        if (canSearch(SearchType::UserModerationComment, $permissions)) {
            $articleTypes[] = ArticleType::UserModeration;
        }
    }

    if (in_array(SearchType::GameHashComment, $searchType)) {
        if (canSearch(SearchType::GameHashComment, $permissions)) {
            $articleTypes[] = ArticleType::GameHash;
        }
    }

    if (in_array(SearchType::SetClaimComment, $searchType)) {
        if (canSearch(SearchType::SetClaimComment, $permissions)) {
            $articleTypes[] = ArticleType::SetClaim;
        }
    }

    if ($articleTypes !== []) {
        $counts[] = "SELECT COUNT(*) AS Count FROM Comment AS c
            LEFT JOIN UserAccounts AS cua ON cua.ID=c.user_id
            LEFT JOIN UserAccounts AS ua ON ua.ID=c.ArticleID AND c.articletype=" . ArticleType::User . "
            WHERE c.Payload LIKE '%$searchQuery%'
            AND cua.User != 'Server' AND c.articletype IN (" . implode(',', $articleTypes) . ")
            AND ua.Deleted IS NULL AND (ua.UserWallActive OR ua.UserWallActive IS NULL)";
        $parts[] = "
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
                WHEN c.articletype=" . ArticleType::Game . " THEN CONCAT('/game/', c.ArticleID, '#comment_', c.ID)
                WHEN c.articletype=" . ArticleType::Achievement . " THEN CONCAT('/achievement/', c.ArticleID, '#comment_', c.ID)
                WHEN c.articletype=" . ArticleType::Leaderboard . " THEN CONCAT('/leaderboardinfo.php?i=', c.ArticleID, '#comment_', c.ID)
                WHEN c.articletype=" . ArticleType::AchievementTicket . " THEN CONCAT('/ticket/', c.ArticleID, '#comment_', c.ID)
                WHEN c.articletype=" . ArticleType::User . " THEN CONCAT('/user/', ua.display_name, '#comment_', c.ID)
                WHEN c.articletype=" . ArticleType::UserModeration . " THEN CONCAT('/user/', ua.display_name, '#comment_', c.ID)
                WHEN c.articletype=" . ArticleType::GameHash . " THEN CONCAT('/game/', c.ArticleID, '/hashes/manage', '#comment_', c.ID)
                WHEN c.articletype=" . ArticleType::SetClaim . " THEN CONCAT('/manageclaims.php?g=', c.ArticleID, '#comment_', c.ID)
                ELSE CONCAT(c.articletype, '/', c.ArticleID)
            END AS Target,
            CASE
                WHEN CHAR_LENGTH(c.Payload) <= 64 THEN c.Payload
                ELSE CONCAT( '...', MID( c.Payload, GREATEST( LOCATE('$searchQuery', c.Payload)-25, 1), 60 ), '...' )
            END AS Title
            FROM Comment AS c
            LEFT JOIN UserAccounts AS cua ON cua.ID=c.user_id
            LEFT JOIN UserAccounts AS ua ON ua.ID=c.ArticleID AND c.articletype in (" . ArticleType::User . "," . ArticleType::UserModeration . ")
            WHERE c.Payload LIKE '%$searchQuery%'
            AND cua.User != 'Server' AND c.articletype IN (" . implode(',', $articleTypes) . ")
            AND ua.Deleted IS NULL AND (ua.UserWallActive OR ua.UserWallActive IS NULL)
            ORDER BY c.articletype, c.Submitted DESC";
    }

    $resultCount = 0;
    $partsCount = count($parts);
    for ($i = 0; $i < $partsCount; $i++) {
        // determine how many rows this subquery would return
        $query = $counts[$i];

        $dbResult = s_mysql_query($query);
        if (!$dbResult) {
            log_sql_fail();

            return 0;
        }

        $partCount = mysqli_fetch_assoc($dbResult)['Count'];
        if ($partCount == 0) {
            continue;
        }

        // tally up the results that would be returned by this subquery
        $resultCount += $partCount;

        if ($count <= 0) {
            // already have all the requested results. proceed to next subquery
            continue;
        }

        if ($offset > $partCount) {
            // subquery does not return at least $offset records. proceed to next subquery
            $offset -= $partCount;
            continue;
        }

        // fetch the results for this subquery
        $query = $parts[$i] . " LIMIT $offset, $count";

        $dbResult = s_mysql_query($query);
        if (!$dbResult) {
            log_sql_fail();

            return 0;
        }

        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $searchResultsOut[] = $nextData;
            $count--;
        }

        if ($count <= 0 && !$wantTotalResults) {
            break;
        }
    }

    return $resultCount;
}
