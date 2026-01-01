<?php

use App\Community\Enums\CommentableType;
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
    bool $wantTotalResults = true,
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
        $counts[] = "SELECT COUNT(*) AS Count FROM games WHERE title LIKE '%$searchQuery%'";
        $parts[] = "
        SELECT " . SearchType::Game . " AS Type, gd.id AS ID, CONCAT( '/game/', gd.id ) AS Target,
               CONCAT(gd.title, ' (', s.name, ')') AS Title,
               CASE
                   WHEN gd.title LIKE '$searchQuery%' THEN 0
                   WHEN gd.title LIKE '%~ $searchQuery%' THEN 1
                   ELSE 2
               END AS SecondarySort
        FROM games AS gd
        LEFT JOIN achievements AS ach ON ach.game_id = gd.id AND ach.is_promoted = 1
        LEFT JOIN systems AS s ON gd.system_id = s.id
        WHERE gd.system_id != 100
        AND gd.title LIKE '%$searchQuery%'
        GROUP BY gd.id, gd.title
        ORDER BY SecondarySort, REPLACE(gd.title, '|', ''), gd.title";
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
        $counts[] = "SELECT COUNT(*) AS Count FROM achievements WHERE title LIKE '%$searchQuery%'";
        $parts[] = "
        SELECT " . SearchType::Achievement . " AS Type, ach.id, CONCAT( '/achievement/', ach.id ) AS Target,
               CONCAT(ach.title, ' (', gd.title, ')') AS Title,
               CASE WHEN ach.title LIKE '$searchQuery%' THEN 0 ELSE 1 END AS SecondarySort
        FROM achievements AS ach
        LEFT JOIN games AS gd ON gd.id = ach.game_id
        WHERE ach.is_promoted = 1 AND ach.title LIKE '%$searchQuery%'
        ORDER BY SecondarySort, ach.title";
    }

    if (in_array(SearchType::User, $searchType)) {
        $counts[] = "SELECT COUNT(*) AS Count FROM users WHERE display_name LIKE '%$searchQuery%'";
        $parts[] = "
            SELECT " . SearchType::User . " AS Type, ua.display_name AS ID,
                CONCAT( '/user/', ua.display_name ) AS Target, ua.display_name AS Title,
                CASE WHEN ua.display_name LIKE '$searchQuery%' THEN 0 ELSE 1 END AS SecondarySort
            FROM users AS ua
            WHERE ua.display_name LIKE '%$searchQuery%' AND ua.Permissions >= 0 AND ua.deleted_at IS NULL
            ORDER BY SecondarySort, ua.display_name";
    }

    if (in_array(SearchType::Forum, $searchType)) {
        $counts[] = "SELECT COUNT(*) AS Count FROM forum_topic_comments WHERE body LIKE '%$searchQuery%'";
        $parts[] = "
        SELECT " . SearchType::Forum . " AS Type, ua.username AS ID,
               CONCAT( '/forums/topic/', ftc.forum_topic_id, '?comment=', ftc.id, '#', ftc.id ) AS Target,
               CASE WHEN CHAR_LENGTH(ftc.body) <= 64 THEN ftc.body ELSE
               CONCAT( '...', MID( ftc.body, GREATEST( LOCATE('$searchQuery', ftc.body)-25, 1), 60 ), '...' ) END AS Title
        FROM forum_topic_comments AS ftc
        LEFT JOIN users AS ua ON ua.id = ftc.author_id
        LEFT JOIN forum_topics AS ft ON ft.id = ftc.forum_topic_id
        WHERE ftc.body LIKE '%$searchQuery%' AND ft.deleted_at IS NULL
        AND ft.required_permissions <= '$permissions'
        GROUP BY ID, ftc.id
        ORDER BY IFNULL(ftc.updated_at, ftc.created_at) DESC";
    }

    $commentableTypes = [];

    if (in_array(SearchType::GameComment, $searchType)) {
        $commentableTypes[] = CommentableType::Game->value;
    }

    if (in_array(SearchType::AchievementComment, $searchType)) {
        $commentableTypes[] = CommentableType::Achievement->value;
    }

    if (in_array(SearchType::LeaderboardComment, $searchType)) {
        $commentableTypes[] = CommentableType::Leaderboard->value;
    }

    $includeTicketComments = false;
    if (in_array(SearchType::TicketComment, $searchType)) {
        if (canSearch(SearchType::GameHashComment, $permissions)) {
            $commentableTypes[] = CommentableType::AchievementTicket->value;
            $includeTicketComments = true;
        }
    }

    if (in_array(SearchType::UserComment, $searchType)) {
        $commentableTypes[] = CommentableType::User->value;
    }

    if (in_array(SearchType::UserModerationComment, $searchType)) {
        if (canSearch(SearchType::UserModerationComment, $permissions)) {
            $commentableTypes[] = CommentableType::UserModeration->value;
        }
    }

    if (in_array(SearchType::GameHashComment, $searchType)) {
        if (canSearch(SearchType::GameHashComment, $permissions)) {
            $commentableTypes[] = CommentableType::GameHash->value;
        }
    }

    if (in_array(SearchType::SetClaimComment, $searchType)) {
        if (canSearch(SearchType::SetClaimComment, $permissions)) {
            $commentableTypes[] = CommentableType::SetClaim->value;
        }
    }

    if ($commentableTypes !== []) {
        // Count regular comments.
        $commentableTypesQuoted = "'" . implode("','", $commentableTypes) . "'";
        $countsQuery = "SELECT COUNT(*) AS Count FROM comments AS c
            LEFT JOIN users AS cua ON cua.id=c.user_id
            LEFT JOIN users AS ua ON ua.id=c.commentable_id AND c.commentable_type='" . CommentableType::User->value . "'
            WHERE c.body LIKE '%$searchQuery%'
            AND cua.username != 'Server' AND c.commentable_type IN (" . $commentableTypesQuoted . ")
            AND ua.deleted_at IS NULL AND (ua.is_user_wall_active OR ua.is_user_wall_active IS NULL)";

        // If searching ticket comments, also count body from tickets.
        if ($includeTicketComments) {
            $countsQuery = "SELECT SUM(Count) AS Count FROM (
                $countsQuery
                UNION ALL
                SELECT COUNT(*) AS Count FROM tickets AS t
                LEFT JOIN users AS reporter ON reporter.id=t.reporter_id
                WHERE t.body LIKE '%$searchQuery%'
                AND reporter.username != 'Server'
                AND t.deleted_at IS NULL
            ) AS combined_counts";
        }

        $counts[] = $countsQuery;

        // Build the query for regular comments.
        $partsQuery = "
            SELECT CASE
                WHEN c.commentable_type='" . CommentableType::Game->value . "' THEN " . SearchType::GameComment . "
                WHEN c.commentable_type='" . CommentableType::Achievement->value . "' THEN " . SearchType::AchievementComment . "
                WHEN c.commentable_type='" . CommentableType::Leaderboard->value . "' THEN " . SearchType::LeaderboardComment . "
                WHEN c.commentable_type='" . CommentableType::AchievementTicket->value . "' THEN " . SearchType::TicketComment . "
                WHEN c.commentable_type='" . CommentableType::User->value . "' THEN " . SearchType::UserComment . "
                WHEN c.commentable_type='" . CommentableType::UserModeration->value . "' THEN " . SearchType::UserModerationComment . "
                WHEN c.commentable_type='" . CommentableType::GameHash->value . "' THEN " . SearchType::GameHashComment . "
                WHEN c.commentable_type='" . CommentableType::SetClaim->value . "' THEN " . SearchType::SetClaimComment . "
                ELSE 9999
            END AS Type,
            cua.username AS ID,
            CASE
                WHEN c.commentable_type='" . CommentableType::Game->value . "' THEN CONCAT('/game/', c.commentable_id, '#comment_', c.id)
                WHEN c.commentable_type='" . CommentableType::Achievement->value . "' THEN CONCAT('/achievement/', c.commentable_id, '#comment_', c.id)
                WHEN c.commentable_type='" . CommentableType::Leaderboard->value . "' THEN CONCAT('/leaderboardinfo.php?i=', c.commentable_id, '#comment_', c.id)
                WHEN c.commentable_type='" . CommentableType::AchievementTicket->value . "' THEN CONCAT('/ticket/', c.commentable_id, '#comment_', c.id)
                WHEN c.commentable_type='" . CommentableType::User->value . "' THEN CONCAT('/user/', ua.display_name, '#comment_', c.id)
                WHEN c.commentable_type='" . CommentableType::UserModeration->value . "' THEN CONCAT('/user/', ua.display_name, '#comment_', c.id)
                WHEN c.commentable_type='" . CommentableType::GameHash->value . "' THEN CONCAT('/game/', c.commentable_id, '/hashes/manage', '#comment_', c.id)
                WHEN c.commentable_type='" . CommentableType::SetClaim->value . "' THEN CONCAT('/manageclaims.php?g=', c.commentable_id, '#comment_', c.id)
                ELSE CONCAT(c.commentable_type, '/', c.commentable_id)
            END AS Target,
            CASE
                WHEN CHAR_LENGTH(c.body) <= 64 THEN c.body
                ELSE CONCAT( '...', MID( c.body, GREATEST( LOCATE('$searchQuery', c.body)-25, 1), 60 ), '...' )
            END AS Title,
            c.created_at AS SortDate
            FROM comments AS c
            LEFT JOIN users AS cua ON cua.id=c.user_id
            LEFT JOIN users AS ua ON ua.id=c.commentable_id AND c.commentable_type in ('" . CommentableType::User->value . "','" . CommentableType::UserModeration->value . "')
            WHERE c.body LIKE '%$searchQuery%'
            AND cua.username != 'Server' AND c.commentable_type IN (" . $commentableTypesQuoted . ")
            AND ua.deleted_at IS NULL AND (ua.is_user_wall_active OR ua.is_user_wall_active IS NULL)";

        // If searching ticket comments, also include body from tickets via a UNION.
        if ($includeTicketComments) {
            $partsQuery = "
                SELECT Type, ID, Target, Title, SortDate FROM (
                    $partsQuery
                    UNION ALL
                    SELECT " . SearchType::TicketComment . " AS Type,
                        reporter.username AS ID,
                        CONCAT('/ticket/', t.id) AS Target,
                        CASE
                            WHEN CHAR_LENGTH(t.body) <= 64 THEN t.body
                            ELSE CONCAT( '...', MID( t.body, GREATEST( LOCATE('$searchQuery', t.body)-25, 1), 60 ), '...' )
                        END AS Title,
                        t.created_at AS SortDate
                    FROM tickets AS t
                    LEFT JOIN users AS reporter ON reporter.id=t.reporter_id
                    WHERE t.body LIKE '%$searchQuery%'
                    AND reporter.username != 'Server'
                    AND t.deleted_at IS NULL
                ) AS combined_results
                ORDER BY Type, SortDate DESC";
        } else {
            $partsQuery .= " ORDER BY c.commentable_type, c.created_at DESC";
        }

        $parts[] = $partsQuery;
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
