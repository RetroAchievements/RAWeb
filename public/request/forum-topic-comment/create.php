<?php

use Illuminate\Support\Facades\Validator;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(request()->post(), [
    'topic' => 'required|integer|exists:mysql_legacy.ForumTopic,ID',
    'body' => 'required|string|max:60000',
]);

$topicID = (int) $input['topic'];

if (submitTopicComment($user, $topicID, null, $input['body'], $newCommentID)) {
    return redirect(url("/viewtopic.php?t=$topicID&c=$newCommentID"))->with('success', __('legacy.success.send'));
}

return back()->withErrors(__('legacy.error.error'));
