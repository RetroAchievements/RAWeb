<?php

use App\Models\ForumTopic;
use App\Models\User;
use App\Support\Rules\ContainsRegularCharacter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'topic' => 'required|integer|exists:forum_topics,id',
    'body' => [
        'required',
        'string',
        'max:60000',
        new ContainsRegularCharacter(),
    ],
]);

$userModel = User::whereName($user)->first();
$forumTopic = ForumTopic::find((int) $input['topic']);

if (!$forumTopic || !$userModel->can('create', [App\Models\ForumTopicComment::class, $forumTopic])) {
    return back()->withErrors(__('legacy.error.error'));
}

$newComment = submitTopicComment($userModel, $forumTopic->id, null, $input['body']);

return redirect(
    route('forum-topic.show', ['topic' => $forumTopic->id, 'comment' => $newComment->id])
)->with('success', __('legacy.success.send'));
