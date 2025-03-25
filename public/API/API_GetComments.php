<?php

use App\Actions\FindUserByIdentifierAction;
use App\Models\Comment;
use App\Models\User;
use App\Policies\CommentPolicy;
use App\Policies\UserCommentPolicy;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/*
*  API_GetComments - returns the comments associated to a game, achievement, or user wall
*    i : game id, achievement id, username, or user ulid
*    t : 1 = game, 2 = achievement, 3 = user
*    o : offset - number of entries to skip (default: 0)
*    c : count - number of entries to return (default: 100, max: 500)
*    s : sortOrder - sort comments. 'submitted' = ascending, '-submitted' = descending (default: 'submitted')
*
*  int         Count                       number of comment records returned in the response
*  int         Total                       number of comment records the game/achievement/user actually has overall
*  array       Results
*   object      [value]
*    string      User                      username of the commenter
*    string      ULID                      queryable stable unique identifier of the commenter
*    string      Submitted                 date time the comment was submitted
*    string      CommentText               text of the comment
*/

$query = request()->query();

$inputIsGameOrAchievement = function () use ($query) {
    return isset($query['i']) && is_numeric($query['i']) && intval($query['i']) == $query['i'];
};

$sortOptions = [
    'submitted' => 'asc',
    '-submitted' => 'desc',
];

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
    's' => [
        'sometimes',
        'string',
        Rule::in(array_keys($sortOptions)),
        'nullable',
    ],
];

$input = Validator::validate(Arr::wrap($query), $rules);

$offset = $input['o'] ?? 0;
$count = $input['c'] ?? 100;
$sortOrder = isset($input['s']) ? $input['s'] : 'submitted';

$usernameOrUlid = null;
$gameOrAchievementId = 0;
$commentType = 0;

if ($inputIsGameOrAchievement()) {
    $gameOrAchievementId = $query['i'];
    $commentType = $query['t'];
} else {
    $usernameOrUlid = $query['i'];
    $commentType = 3;
}

$user = null;
$userPolicy = new UserCommentPolicy();

if ($usernameOrUlid) {
    $user = (new FindUserByIdentifierAction())->execute($usernameOrUlid);
    if (!$user || !$userPolicy->viewAny(null, $user)) {
        return response()->json([], 404);
    }
}

$articleId = $user ? $user->ID : $gameOrAchievementId;

$commentsQuery = Comment::withTrashed()
    ->with('user')
    ->where('ArticleType', $commentType)
    ->where('ArticleID', $articleId)
    ->whereNull('deleted_at')
    ->whereHas('user', function ($query) {
        $query->whereNull('banned_at');
    })
    ->offset($offset)
    ->limit($count);

$commentsQuery->orderBy('Submitted', $sortOptions[$sortOrder]);

$comments = $commentsQuery->get();

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
        'User' => $nextComment->user->display_name,
        'ULID' => $nextComment->user->ulid,
        'Submitted' => $nextComment->Submitted,
        'CommentText' => $nextComment->Payload,
    ];
});

return response()->json([
    'Count' => $results->count(),
    'Total' => $totalComments,
    'Results' => $results,
]);
