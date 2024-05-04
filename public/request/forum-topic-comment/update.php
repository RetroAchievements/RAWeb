<?php

use App\Enums\Permissions;
use App\Models\ForumTopicComment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'comment' => 'required|integer|exists:ForumTopicComment,ID',
    'body' => 'required|string|max:60000',
]);

$forumTopicComment = ForumTopicComment::find((int) $input['comment']);

if (!$forumTopicComment || !$user->can('update', $forumTopicComment)) {
    return back()->withErrors(__('legacy.error.error'));
}

$commentId = $forumTopicComment->id;
$commentPayload = $input['body'];

$topicId = $forumTopicComment->forum_topic_id;
if (editTopicComment($commentId, $commentPayload)) {
    return redirect(url("/viewtopic.php?t=$topicId&c=$commentId#$commentId"))->with('success', __('legacy.success.update'));
}

return back()->withErrors(__('legacy.error.error'));
