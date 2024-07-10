<?php

use App\Models\Achievement;
use App\Models\Comment;
use App\Models\User;
use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Facades\Validator;
use App\Policies\CommentPolicy;

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

if ($username) {
    $user = User::firstWhere('User', $username);

    if (!$user || !$user->UserWallActive) {
        return [];
    }

    $comments = Comment::where('ArticleType', $commentType)
                                    ->offset($offset)
                                    ->limit($count)
                                    ->where('ArticleID', $user->ID)
                                    ->get();
} else {
    $comments = Comment::where('ArticleType', $commentType)
                                    ->offset($offset)
                                    ->limit($count)
                                    ->where('ArticleID', $gameOrAchievementId)
                                    ->get();
}

$results = [];

$policy = new CommentPolicy();

if (!empty($comments)) {
    foreach ($comments as $nextComment) {
        $user = User::firstWhere('ID', $nextComment['user_id']);

        

        if ($user && $policy->view(request()->user(), $nextComment)) {
            $commentData = [
                'User' => $user->username,
                'Submitted' => $nextComment->Submitted,
                'CommentText' => $nextComment->Payload,
            ];

            array_push($results, $commentData);
        }
    }
}

return response()->json([
    'Count' => count($results),
    'Total' => count($comments),
    'Results' => $results,
]);
