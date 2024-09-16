<?php

use App\Models\Achievement;
use App\Models\Comment;
use App\Models\User;
use App\Policies\CommentPolicy;
use App\Policies\UserCommentPolicy;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/*
*  API_GetComments - returns the comments associated to a game or achievement
*    i : game or achievement id or username
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

$query = request()->query();

$inputIsGameOrAchievement = function () use ($query) {
    return isset($query['i']) && is_numeric($query['i']) && intval($query['i']) == $query['i'];
};

$rules = [
    'i' => [
        'required',
        Rule::when(isset($query['t']) && $query['t'] === '3', 'string'),
        Rule::when(isset($query['t']) && in_array($query['t'], [1, 2]), 'integer'),
    ],
    't' => [
        Rule::requiredIf($inputIsGameOrAchievement()),
        'integer',
    ],
    'o' => ['sometimes', 'integer', 'min:0', 'nullable'],
    'c' => ['sometimes', 'integer', 'min:1', 'max:500', 'nullable'],
];

$input = Validator::validate(Arr::wrap($query), $rules);

$offset = $input['o'] ?? 0;
$count = $input['c'] ?? 100;

$username = null;
$gameOrAchievementId = 0;
$commentType = 0;

if ($inputIsGameOrAchievement()) {
    $gameOrAchievementId = $query['i'];
    $commentType = $query['t'];
} else {
    $username = $query['i'];
    $commentType = 3;
}

$user = null;
$userPolicy = new UserCommentPolicy();

if ($username) {
    $user = User::firstWhere('User', $username);

    if (!$user || !$userPolicy->viewAny(null, $user)) {
        return response()->json([], 404);
    }
}

$articleId = $user ? $user->ID : $gameOrAchievementId;

$comments = Comment::withTrashed()
    ->with('user')
    ->where('ArticleType', $commentType)
    ->where('ArticleID', $articleId)
    ->whereNull('deleted_at')
    ->whereHas('user', function ($query) {
        $query->whereNull('banned_at');
    })
    ->offset($offset)
    ->limit($count)
    ->get();

$totalComments = Comment::withTrashed()
    ->where('ArticleType', $commentType)
    ->where('ArticleID', $articleId)
    ->whereNull('deleted_at')
    ->whereHas('user', function ($query) {
        $query->whereNull('banned_at');
    })
    ->count();

$commentPolicy = new CommentPolicy();

$results = $comments->filter(function ($nextComment) use ($commentPolicy) {
    $user = Auth::user() instanceof User ? Auth::user() : null;

    return $commentPolicy->view($user, $nextComment);
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
