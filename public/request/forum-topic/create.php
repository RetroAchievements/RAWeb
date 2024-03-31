<?php

use App\Enums\Permissions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

if ($userDetails['isMuted']) {
    return back()->withErrors(__('legacy.error.error'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'forum' => 'required|integer|exists:Forum,ID',
    'title' => 'required|string|max:255',
    'body' => 'required|string|max:60000',
]);

$topicID = null;
if (submitNewTopic($user, (int) $input['forum'], $input['title'], $input['body'], $topicID)) {
    return redirect(url("/viewtopic.php?t=$topicID"))->with('success', __('legacy.success.create'));
}

return back()->withErrors(__('legacy.error.error'));
