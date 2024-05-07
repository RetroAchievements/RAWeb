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

$newComment = submitTopicComment($user, $forumTopic->id, null, $input['body']);

return redirect(url("/viewtopic.php?t={$forumTopic->id}&c={$newComment->id}"))->with('success', __('legacy.success.send'));
