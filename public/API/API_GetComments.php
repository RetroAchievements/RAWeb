<?php

use App\Models\Achievement;
use App\Models\Comment;
use App\Models\User;
use App\Policies\CommentPolicy;
use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Facades\Validator;

/*
*  API_GetComments - returns the comments associated to a game or achievement
*    i : game or achievement id
*    u : username
*    t : 1 = game, 2 = achievement, 3 = user
*    o : offset - number of entries to skip (default: 0)
*    c : count - number of entries to return (default: 100, max: 500)
*
*  int         Count                       number of comment records returned in the response
*  int         Total                       number of comment records the game/achievement/user actually has overall
*  array       Results
*   object      [value]
*    int         User                      username of the commenter
*    string      Submitted                 date time the comment was submitted
*    string      CommentText               text of the comment
*/

$input = Validator::validate(Arr::wrap(request()->query()), [
    'i' => ['sometimes', 'integer'],
    't' => ['required', 'integer'],
    'u' => ['sometimes', 'min:2', 'max:20', new CtypeAlnum()],
    'o' => ['sometimes', 'integer', 'min:0', 'nullable'],
    'c' => ['sometimes', 'integer', 'min:1', 'max:500', 'nullable'],
]);

$offset = $input['o'] ?? 0;
$count = $input['c'] ?? 100;

$gameOrAchievementId = (int) request()->query('i');
$username = (string) request()->query('u');
$commentType = (int) request()->query('t');

$user = null;

if ($username) {
    $user = User::firstWhere('User', $username);
    if (!$user || !$user->UserWallActive) {
        return response()->json([], 404);
    }
}

$articleId = $user ? $user->ID : $gameOrAchievementId;

$comments = Comment::withTrashed()
    ->where('ArticleType', $commentType)
    ->where('ArticleID', $articleId)
    ->whereNull('deleted_at')
    ->offset($offset)
    ->limit($count)
    ->with('user')
    ->get();

$totalComments = Comment::withTrashed()
    ->where('ArticleType', $commentType)
    ->where('ArticleID', $articleId)
    ->whereNull('deleted_at')
    ->whereHas('user', function ($query) {
        $query->whereNull('banned_at');
    })
    ->count();

$policy = new CommentPolicy();

$results = $comments->filter(function ($nextComment) use ($policy) {
    return $policy->view($nextComment->user, $nextComment);
})->map(function ($nextComment) {
    return [
        'User' => $nextComment->user->username,
        'Submitted' => $nextComment->Submitted,
        'CommentText' => $nextComment->Payload,
    ];
});

return response()->json([
    'Count' => $results->count(),
    'Total' => $totalComments,
    'Results' => $results,
]);
