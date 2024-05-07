<?php

use App\Models\Forum;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'forum' => 'required|integer|exists:Forum,ID',
    'title' => 'required|string|max:255', // TODO enforce a min length
    'body' => 'required|string|max:60000',
]);

$userModel = User::firstWhere('User', $user);
$forum = Forum::find((int) $input['forum']);

if (!$forum || !$userModel->can('create', [App\Models\ForumTopic::class, $forum])) {
    return back()->withErrors(__('legacy.error.error'));
}

$forumTopicComment = submitNewTopic($userModel, $forum->id, $input['title'], $input['body']);

return redirect(url("/viewtopic.php?t={$forumTopicComment->forumTopic->id}"))
    ->with('success', __('legacy.success.create'));
