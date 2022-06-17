<?php

use Illuminate\Support\Facades\Validator;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(request()->post(), [
    'comment' => 'required|integer|exists:mysql_legacy.ForumTopicComment,ID',
    'body' => 'required|string|max:60000',
]);

$commentID = $input['comment'];
$commentPayload = $input['body'];

if (!getSingleTopicComment($commentID, $commentData)) {
    return back()->withErrors(__('legacy.error.error'));
}

if ($user != $commentData['Author'] && $permissions < Permissions::Admin) {
    return back()->withErrors(__('legacy.error.permissions'));
}

if (editTopicComment($commentID, $commentPayload)) {
    return back()->with('success', __('legacy.success.update'));
}

return back()->withErrors(__('legacy.error.error'));
