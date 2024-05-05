<?php

use App\Enums\Permissions;
use App\Models\ForumTopicComment;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'comment' => 'required|integer|exists:ForumTopicComment,ID',
    'body' => 'required|string|max:60000',
]);

$userModel = User::firstWhere('User', $user);
$forumTopicComment = ForumTopicComment::find((int) $input['comment']);

if (!$forumTopicComment || !$userModel->can('update', $forumTopicComment)) {
    return back()->withErrors(__('legacy.error.error'));
}

$commentId = $forumTopicComment->id;
$commentPayload = $input['body'];

$topicId = $forumTopicComment->forum_topic_id;
if (editTopicComment($commentId, $commentPayload)) {
    return redirect(url("/viewtopic.php?t=$topicId&c=$commentId#$commentId"))->with('success', __('legacy.success.update'));
}

return back()->withErrors(__('legacy.error.error'));
