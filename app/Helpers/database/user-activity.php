<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Comment;
use App\Models\PlayerGame;
use App\Models\System;
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
    $lastComment = Comment::where('user_id', $userID)
        ->orderBy('Submitted', 'desc')
        ->first();

    // If there are no comments at all, then this isn't a double post.
    if (!$lastComment) {
        return false;
    }

    return
        $lastComment->Payload === $commentPayload
        && $lastComment->ArticleID === $articleID;
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
    if ($userID === 0) {
        return false;
    }

    if ($user !== "Server" && getIsCommentDoublePost($userID, $articleID, $commentPayload)) {
        // Fail silently.
        return true;
    }

    $articleIDs = is_array($articleID) ? $articleID : [$articleID];
    foreach ($articleIDs as $id) {
        $comment = Comment::create([
            'ArticleType' => $articleType,
            'ArticleID' => $id,
            'user_id' => $userID,
            'Payload' => $commentPayload,
        ]);

        informAllSubscribersAboutActivity($articleType, $id, $user, $comment->ID, $onBehalfOfUser);
    }

    return true;
}

function expireRecentlyPlayedGames(string $user): void
{
    $userRecentGamesCacheKey = CacheKey::buildUserRecentGamesCacheKey($user);
    Cache::forget($userRecentGamesCacheKey);
}

function getRecentlyPlayedGames(User $user, int $offset, int $count, ?array &$dataOut): int
{
    $dataOut = [];

    if ($count < 1) {
        return 0;
    }

    $playerGames = PlayerGame::where('user_id', $user->id)
        ->whereHas('game', function ($query) {
            $query->whereNotIn('ConsoleId', System::getNonGameSystems());
        })
        ->with('game.system')
        ->orderByDesc('last_played_at')
        ->offset($offset)
        ->limit($count);

    foreach ($playerGames->get() as $playerGame) {
        $dataOut[] = [
            'GameID' => $playerGame->game->ID,
            'ConsoleID' => $playerGame->game->system->id,
            'ConsoleName' => $playerGame->game->system->name,
            'Title' => $playerGame->game->Title,
            'ImageIcon' => $playerGame->game->ImageIcon,
            'ImageTitle' => $playerGame->game->ImageTitle,
            'ImageIngame' => $playerGame->game->ImageIngame,
            'ImageBoxArt' => $playerGame->game->ImageBoxArt,
            'LastPlayed' => $playerGame->last_played_at->format("Y-m-d H:i:s"),
            'AchievementsTotal' => $playerGame->game->achievements_published,
        ];
    }

    return count($dataOut);
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
                     ua.RichPresenceMsg, gd.ID AS GameID, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, c.Name AS ConsoleName
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
