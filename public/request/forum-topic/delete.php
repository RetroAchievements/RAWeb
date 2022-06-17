<?php

use App\Legacy\Models\DeletedModels;
use App\Legacy\Models\ForumTopic;
use App\Legacy\Models\User;
use Illuminate\Support\Facades\Validator;
use RA\Permissions;

if (!authenticateFromCookie($username, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

/** @var User $user */
$user = request()->user();

$input = Validator::validate(request()->post(), [
    'topic' => 'required|integer|exists:mysql_legacy.ForumTopic,ID',
]);

/** @var ForumTopic $topic */
$topic = ForumTopic::find($input['topic']);
$topic->delete();

DeletedModels::create([
    'ModelType' => 'ForumTopic',
    'ModelID' => $input['topic'],
    'DeletedByUserID' => $user->ID,
]);

return back()->with('success', __('legacy.success.delete'));
