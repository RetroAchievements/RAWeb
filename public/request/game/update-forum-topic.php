<?php

use App\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Moderator)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
    'forum_topic' => 'required|integer|exists:ForumTopic,ID',
]);

if (modifyGameForumTopic($user, (int) $input['game'], (int) $input['forum_topic'])) {
    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
