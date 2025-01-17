<?php

use App\Enums\Permissions;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
]);

$userModel = User::whereName($user)->first();

$forumTopicComment = generateGameForumTopic($userModel, (int) $input['game']);
if ($forumTopicComment) {
    return redirect(url("/viewtopic.php?t={$forumTopicComment->forumTopic->id}"))
        ->with('success', __('legacy.success.create'));
}

return back()->withErrors(__('legacy.error.error'));
