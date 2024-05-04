<?php

use App\Models\Forum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'forum' => 'required|integer|exists:Forum,ID',
    'title' => 'required|string|max:255',
    'body' => 'required|string|max:60000',
]);

$forum = Forum::find((int) $input['forum']);

if (!$forum || !$user->can('create', [App\Models\ForumTopic::class, $forum])) {
    return back()->withErrors(__('legacy.error.error'));
}

$topicID = null;
if (submitNewTopic($user->username, $forum->id, $input['title'], $input['body'], $topicID)) {
    return redirect(url("/viewtopic.php?t=$topicID"))->with('success', __('legacy.success.create'));
}

return back()->withErrors(__('legacy.error.error'));
