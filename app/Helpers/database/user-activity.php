<?php

use App\Community\Enums\CommentableType;
use App\Models\Comment;
use App\Models\PlayerGame;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
            $query->whereGameSystem();
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
    $direction = $recent ? 'desc' : 'asc';

    $submittedStatement = unixTimestampStatement('c.created_at', 'Submitted');

    $query = DB::table('comments as c')
        ->select([
            'ua.username as User',
            'ua.banned_at',
            'c.id as ID',
            'c.user_id',
            'c.body as CommentPayload',
            DB::raw($submittedStatement),
            'c.updated_at as Edited',
        ])
        ->leftJoin('users as ua', 'ua.id', '=', 'c.user_id')
        ->where('c.commentable_type', $commentableType->value)
        ->where('c.commentable_id', $commentableId)
        ->whereNull('c.deleted_at')
        ->orderBy('c.created_at', $direction)
        ->orderBy('c.id', $direction)
        ->offset($offset)
        ->limit($count);

    foreach ($query->get() as $dbEntry) {
        $dataOut[$numArticleComments] = (array) $dbEntry;
        $numArticleComments++;
    }

    if ($offset != 0 || $numArticleComments >= $count) {
        $numArticleComments = Comment::query()
            ->where('commentable_type', $commentableType->value)
            ->where('commentable_id', $commentableId)
            ->count();
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
