<?php

use App\Models\ForumTopic;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'topic' => 'required|integer|exists:ForumTopic,ID',
    'body' => 'required|string|max:60000',
]);

$forumTopic = ForumTopic::find((int) $input['topic']);

if (!$forumTopic || !$user->can('create', [App\Models\ForumTopicComment::class, $forumTopic])) {
    return back()->withErrors(__('legacy.error.error'));
}

$topicId = $forumTopic->id;

if (submitTopicComment($user->username, $topicId, null, $input['body'], $newCommentID)) {
    return redirect(url("/viewtopic.php?t=$topicId&c=$newCommentID"))->with('success', __('legacy.success.send'));
}

return back()->withErrors(__('legacy.error.error'));
