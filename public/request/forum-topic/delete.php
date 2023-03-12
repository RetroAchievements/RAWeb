<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use LegacyApp\Community\Models\ForumTopic;
use LegacyApp\Site\Enums\Permissions;
use LegacyApp\Site\Models\User;
use LegacyApp\Support\Database\Models\DeletedModels;

if (!authenticateFromCookie($username, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

/** @var User $user */
$user = request()->user();

$input = Validator::validate(Arr::wrap(request()->post()), [
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
