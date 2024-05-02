<?php

use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'topic' => 'required|integer|exists:ForumTopic,ID',
    'body' => 'required|string|max:60000',
]);

$userModel = User::firstWhere('User', $user);
$forumTopic = ForumTopic::find((int) $input['topic']);

if (!$forumTopic || !$userModel->can('create', [App\Models\ForumTopicComment::class, $forumTopic])) {
    return back()->withErrors(__('legacy.error.error'));
}

$topicId = $forumTopic->id;

if (submitTopicComment($user, $topicId, null, $input['body'], $newCommentID)) {
    return redirect(url("/viewtopic.php?t=$topicId&c=$newCommentID"))->with('success', __('legacy.success.send'));
}

return back()->withErrors(__('legacy.error.error'));
