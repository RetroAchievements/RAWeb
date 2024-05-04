<?php

use App\Models\ForumTopic;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'topic' => 'required|integer|exists:ForumTopic,ID',
    'title' => 'required|string|max:255',
]);

/** @var ForumTopic $forumTopic */
$forumTopic = ForumTopic::find((int) $input['topic']);

if (!$user->can('update', $forumTopic)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$forumTopic->title = $input['title'];
$forumTopic->save();

return back()->with('success', __('legacy.success.modify'));
