<?php

use App\Community\Enums\CommentableType;
use App\Enums\Permissions;
use App\Models\Comment;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;

function getIsCommentDoublePost(int $userID, array|int $commentableId, string $body): bool
{
    $lastComment = Comment::where('user_id', $userID)
        ->orderBy('created_at', 'desc')
        ->first();

    // If there are no comments at all, then this isn't a double post.
    if (!$lastComment) {
        return false;
    }

    return
        $lastComment->body === $body
        && $lastComment->commentable_id === $commentableId;
}

function addArticleComment(
    string $user,
    CommentableType $commentableType,
    array|int $commentableId,
    string $body,
    ?string $onBehalfOfUser = null,
): bool {
    // Note: $user is the person who just made a comment.

    $userModel = User::whereName($user)->first();
    if (!$userModel) {
        return false;
    }

    if ($user !== "Server" && getIsCommentDoublePost($userModel->id, $commentableId, $body)) {
        // Fail silently.
        return true;
    }

    $commentableIds = is_array($commentableId) ? $commentableId : [$commentableId];
    foreach ($commentableIds as $id) {
        $comment = Comment::create([
            'commentable_type' => $commentableType,
            'commentable_id' => $id,
            'user_id' => $userModel->id,
            'body' => $body,
        ]);

        informAllSubscribersAboutActivity($commentableType, $id, $userModel, $comment->id, $onBehalfOfUser);
    }

    return true;
}

function getRecentlyPlayedGames(User $user, int $offset, int $count, array &$dataOut): int
{
    $dataOut = [];

    if ($count < 1) {
        return 0;
    }

    $playerGames = PlayerGame::where('user_id', $user->id)
        ->whereHas('game', function ($query) {
            $query->whereNotIn('system_id', System::getNonGameSystems());
        })
        ->with('game.system')
        ->orderByDesc('last_played_at')
        ->offset($offset)
        ->limit($count);

    foreach ($playerGames->get() as $playerGame) {
        $dataOut[] = [
            'GameID' => $playerGame->game->id,
            'ConsoleID' => $playerGame->game->system->id,
            'ConsoleName' => $playerGame->game->system->name,
            'Title' => $playerGame->game->title,
            'ImageIcon' => $playerGame->game->image_icon_asset_path,
            'ImageTitle' => $playerGame->game->image_title_asset_path,
            'ImageIngame' => $playerGame->game->image_ingame_asset_path,
            'ImageBoxArt' => $playerGame->game->image_box_art_asset_path,
            'LastPlayed' => $playerGame->last_played_at
                ? $playerGame->last_played_at->format("Y-m-d H:i:s")
                : null,
            'AchievementsTotal' => $playerGame->game->achievements_published,
        ];
    }

    return count($dataOut);
}

function getArticleComments(
    CommentableType $commentableType,
    int $commentableId,
    int $offset,
    int $count,
    array &$dataOut,
    bool $recent = false,
): int {
    $dataOut = [];
    $numArticleComments = 0;
    $order = $recent ? ' DESC' : '';

    $commentableTypeValue = $commentableType->value;

    $query = "SELECT SQL_CALC_FOUND_ROWS ua.username AS User, ua.banned_at, c.id AS ID, c.user_id,
                     c.body AS CommentPayload,
                     UNIX_TIMESTAMP(c.created_at) AS Submitted, c.updated_at AS Edited
              FROM comments AS c
              LEFT JOIN users AS ua ON ua.id = c.user_id
              WHERE c.commentable_type='$commentableTypeValue' AND c.commentable_id=$commentableId AND c.deleted_at IS NULL
              ORDER BY c.created_at$order, c.id$order
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
    CommentableType $commentableType,
    int $commentableId,
    array &$dataOut,
    int $count = 20,
): int {
    $numArticleComments = getArticleComments($commentableType, $commentableId, 0, $count, $dataOut, true);

    // Fetch the last elements by submitted, but return them here in top-down order.
    $dataOut = array_reverse($dataOut);

    return $numArticleComments;
}

function getLatestRichPresenceUpdates(): array
{
    $playersFound = [];

    $recentMinutes = 10;
    $permissionsCutoff = Permissions::Registered;

    $ifRAPoints = ifStatement('ua.Untracked', 0, 'ua.points_hardcore');
    $ifRASoftcorePoints = ifStatement('ua.Untracked', 0, 'ua.points');
    $timestampStatement = timestampAddMinutesStatement(-$recentMinutes);

    $query = "SELECT ua.username AS User, $ifRAPoints as RAPoints, $ifRASoftcorePoints as RASoftcorePoints,
                     ua.rich_presence AS RichPresenceMsg, gd.id AS GameID, gd.title AS GameTitle, gd.image_icon_asset_path AS GameIcon, s.name AS ConsoleName
              FROM users AS ua
              LEFT JOIN games AS gd ON gd.id = ua.rich_presence_game_id
              LEFT JOIN systems AS s ON s.id = gd.system_id
              WHERE ua.rich_presence_updated_at > $timestampStatement
                AND ua.rich_presence_game_id != 0
                AND ua.Permissions >= $permissionsCutoff
              ORDER BY RAPoints DESC, RASoftcorePoints DESC, ua.username ASC";

    $dbResult = legacyDbFetchAll($query);

    foreach ($dbResult as $dbEntry) {
        $dbEntry['GameID'] = (int) $dbEntry['GameID'];
        $dbEntry['RAPoints'] = (int) $dbEntry['RAPoints'];
        $dbEntry['RASoftcorePoints'] = (int) $dbEntry['RASoftcorePoints'];
        $playersFound[] = $dbEntry;
    }

    return $playersFound;
}
