<?php

use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($username, $permissions, $userDetails)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'topic' => 'required|integer|exists:ForumTopic,ID',
    'title' => 'required|string|max:255',
]);

$userModel = User::firstWhere('User', $username);

/** @var ForumTopic $forumTopic */
$forumTopic = ForumTopic::find((int) $input['topic']);

if (!$userModel->can('update', $forumTopic)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$forumTopic->title = $input['title'];
$forumTopic->save();

return back()->with('success', __('legacy.success.modify'));
