<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use LegacyApp\Community\Models\ForumTopic;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($username, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'topic' => 'required|integer|exists:mysql_legacy.ForumTopic,ID',
    'title' => 'required|string|max:255',
]);

/** @var ForumTopic $topic */
$topic = ForumTopic::find($input['topic']);

if ($permissions < Permissions::Admin && $topic->Author !== $username) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$topic->Title = $input['title'];
$topic->save();

return back()->with('success', __('legacy.success.modify'));
