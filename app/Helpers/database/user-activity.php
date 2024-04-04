<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Comment;
use App\Models\User;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Facades\Cache;

function RemoveComment(int $commentID, int $userID, int $permissions): bool
{
    /** @var Comment $comment */
    $comment = Comment::findOrFail($commentID);

    $articleID = $comment->ArticleID;

    $query = "DELETE FROM Comment WHERE ID = $commentID";

    // if not UserWall's owner nor admin, check if it's the author
    // TODO use policies to explicitly determine ability to delete a comment instead of piggy-backing query specificity
    if ($articleID != $userID && $permissions < Permissions::Moderator) {
        $query .= " AND user_id = $userID";
    }

    $db = getMysqliConnection();
    $dbResult = mysqli_query($db, $query);

    if (!$dbResult) {
        log_sql_fail();

        return false;
    }

    return mysqli_affected_rows($db) > 0;
}

function getIsCommentDoublePost(int $userID, array|int $articleID, string $commentPayload): bool
{
    $query = "SELECT Comment.Payload, Comment.ArticleID
        FROM Comment
        WHERE user_id = :userId
        ORDER BY Comment.Submitted DESC
        LIMIT 1";

    $dbResult = legacyDbFetch($query, ['userId' => $userID]);

    // Otherwise the user can't make their first post.
    if (!$dbResult) {
        return false;
    }

    $retrievedPayload = $dbResult['Payload'];
    $retrievedArticleID = $dbResult['ArticleID'];

    return
        $retrievedPayload === $commentPayload
        && $retrievedArticleID === $articleID
    ;
}

function addArticleComment(
    string $user,
    int $articleType,
    array|int $articleID,
    string $commentPayload,
    ?string $onBehalfOfUser = null,
): bool {
    if (!ArticleType::isValid($articleType)) {
        return false;
    }

    // Note: $user is the person who just made a comment.

    $userID = getUserIDFromUser($user);
    if ($userID == 0) {
        return false;
    }

    if ($user !== "Server" && getIsCommentDoublePost($userID, $articleID, $commentPayload)) {
        // Fail silently.
        return true;
    }

    if (is_array($articleID)) {
        $bindings = [];

        $articleIDs = $articleID;
        $arrayCount = count($articleID);
        $count = 0;
        $query = "INSERT INTO Comment (ArticleType, ArticleID, user_id, Payload) VALUES";
        foreach ($articleID as $id) {
            $bindings['commentPayload' . $count] = $commentPayload;
            $query .= "( $articleType, $id, $userID, :commentPayload$count )";
            if (++$count !== $arrayCount) {
                $query .= ",";
            }
        }
    } else {
        $query = "INSERT INTO Comment (ArticleType, ArticleID, user_id, Payload) VALUES( $articleType, $articleID, $userID, :commentPayload)";
        $bindings = ['commentPayload' => $commentPayload];
        $articleIDs = [$articleID];
    }

    legacyDbStatement($query, $bindings);

    // Inform Subscribers of this comment:
    foreach ($articleIDs as $id) {
        $query = "SELECT MAX(ID) AS CommentID FROM Comment
                  WHERE ArticleType=$articleType AND ArticleID=$id AND user_id=$userID";
        $commentID = legacyDbFetch($query)['CommentID'];

        informAllSubscribersAboutActivity($articleType, $id, $user, $commentID, $onBehalfOfUser);
    }

    return true;
}

function expireRecentlyPlayedGames(string $user): void
{
    $userRecentGamesCacheKey = CacheKey::buildUserRecentGamesCacheKey($user);
    Cache::forget($userRecentGamesCacheKey);
}

function getRecentlyPlayedGames(string $user, int $offset, int $count, ?array &$dataOut): int
{
    if ($count < 1) {
        $dataOut = [];

        return 0;
    }

    $query = "SELECT pg.last_played_at AS LastPlayed, pg.game_id AS GameID, pg.achievements_total
              FROM player_games pg
              INNER JOIN UserAccounts ua ON ua.ID = pg.user_id
              WHERE ua.User = :username
              ORDER BY pg.last_played_at desc
              LIMIT $offset, $count";

    $recentlyPlayedGames = legacyDbFetchAll($query, ['username' => $user])->toArray();

    $numFound = 0;
    $dataOut = [];

    if (!empty($recentlyPlayedGames)) {
        $recentlyPlayedGameIDs = [];
        foreach ($recentlyPlayedGames as $recentlyPlayedGame) {
            $recentlyPlayedGameIDs[] = $recentlyPlayedGame['GameID'];
        }

        // cache may remember more than was asked for
        if ($count < count($recentlyPlayedGameIDs)) {
            $recentlyPlayedGameIDs = array_slice($recentlyPlayedGameIDs, 0, $count);
        }

        // discard anything that's not numeric or the query will fail
        $recentlyPlayedGameIDs = collect($recentlyPlayedGameIDs)
            ->filter(fn ($id) => is_int($id) || is_numeric($id))
            ->implode(',');
        if (empty($recentlyPlayedGameIDs)) {
            return 0;
        }

        $query = "SELECT gd.ID AS GameID, gd.ConsoleID, c.Name AS ConsoleName, gd.Title, gd.ImageIcon, gd.ImageTitle, gd.ImageIngame, gd.ImageBoxArt
                  FROM GameData AS gd LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
                  WHERE gd.ID IN ($recentlyPlayedGameIDs)";

        $gameData = [];
        $dbResult = legacyDbFetchAll($query);
        foreach ($dbResult as $data) {
            settype($data['GameID'], 'integer');
            settype($data['ConsoleID'], 'integer');
            $gameData[$data['GameID']] = $data;
        }

        foreach ($recentlyPlayedGames as $recentlyPlayedGame) {
            $gameID = $recentlyPlayedGame['GameID'];
            if (array_key_exists($gameID, $gameData)) {
                // Exclude games belonging to the "Events" console.
                if ($gameData[$gameID]['ConsoleID'] !== 101) {
                    $gameData[$gameID]['LastPlayed'] = $recentlyPlayedGame['LastPlayed'];
                    $gameData[$gameID]['AchievementsTotal'] = $recentlyPlayedGame['achievements_total'];
                    $dataOut[] = $gameData[$gameID];
                    $numFound++;
                }
            }
        }
    }

    return $numFound;
}

function getArticleComments(
    int $articleTypeID,
    int $articleID,
    int $offset,
    int $count,
    ?array &$dataOut,
    bool $recent = false
): int {
    $dataOut = [];
    $numArticleComments = 0;
    $order = $recent ? ' DESC' : '';

    $query = "SELECT SQL_CALC_FOUND_ROWS ua.User, ua.RAPoints, ua.banned_at, c.ID, c.user_id,
                     c.Payload AS CommentPayload,
                     UNIX_TIMESTAMP(c.Submitted) AS Submitted, c.Edited
              FROM Comment AS c
              LEFT JOIN UserAccounts AS ua ON ua.ID = c.user_id
              WHERE c.ArticleType=$articleTypeID AND c.ArticleID=$articleID
              ORDER BY c.Submitted$order, c.ID$order
              LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numArticleComments] = $db_entry;
            $numArticleComments++;
        }

        if ($offset != 0 || $numArticleComments >= $count) {
            $query = "SELECT FOUND_ROWS() AS NumResults";
            $dbResult = s_mysql_query($query);
            if ($dbResult !== false) {
                $numArticleComments = mysqli_fetch_assoc($dbResult)['NumResults'];
            }
        }
    } else {
        log_sql_fail();
    }

    return (int) $numArticleComments;
}

function getRecentArticleComments(
    int $articleTypeID,
    int $articleID,
    ?array &$dataOut,
    int $count = 20
): int {
    $numArticleComments = getArticleComments($articleTypeID, $articleID, 0, $count, $dataOut, true);

    // Fetch the last elements by submitted, but return them here in top-down order.
    $dataOut = array_reverse($dataOut);

    return $numArticleComments;
}

function getLatestRichPresenceUpdates(): array
{
    $playersFound = [];

    $recentMinutes = 10;
    $permissionsCutoff = Permissions::Registered;

    $ifRAPoints = ifStatement('ua.Untracked', 0, 'ua.RAPoints');
    $ifRASoftcorePoints = ifStatement('ua.Untracked', 0, 'ua.RASoftcorePoints');
    $timestampStatement = timestampAddMinutesStatement(-$recentMinutes);

    $query = "SELECT ua.User, $ifRAPoints as RAPoints, $ifRASoftcorePoints as RASoftcorePoints,
                     ua.muted_until AS MutedUntil, ua.RichPresenceMsg, gd.ID AS GameID, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, c.Name AS ConsoleName
              FROM UserAccounts AS ua
              LEFT JOIN GameData AS gd ON gd.ID = ua.LastGameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE ua.RichPresenceMsgDate > $timestampStatement
                AND ua.LastGameID != 0
                AND ua.Permissions >= $permissionsCutoff
              ORDER BY RAPoints DESC, RASoftcorePoints DESC, ua.User ASC";

    $dbResult = legacyDbFetchAll($query);

    if ($dbResult !== false) {
        foreach ($dbResult as $dbEntry) {
            $dbEntry['GameID'] = (int) $dbEntry['GameID'];
            $dbEntry['RAPoints'] = (int) $dbEntry['RAPoints'];
            $dbEntry['RASoftcorePoints'] = (int) $dbEntry['RASoftcorePoints'];
            $playersFound[] = $dbEntry;
        }
    }

    return $playersFound;
}
