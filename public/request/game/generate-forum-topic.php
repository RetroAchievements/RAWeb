<?php

use App\Site\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
]);

if (generateGameForumTopic($user, (int) $input['game'], $forumTopicID)) {
    return redirect(url("/viewtopic.php?t=$forumTopicID"))->with('success', __('legacy.success.create'));
}

return back()->withErrors(__('legacy.error.error'));
