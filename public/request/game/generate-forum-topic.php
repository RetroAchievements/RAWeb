<?php

use App\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, Permissions::Developer)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
]);

$forumTopicComment = generateGameForumTopic($user, (int) $input['game']);
if ($forumTopicComment) {
    return redirect(url("/viewtopic.php?t={$forumTopicComment->forumTopic->id}"))
        ->with('success', __('legacy.success.create'));
}

return back()->withErrors(__('legacy.error.error'));
