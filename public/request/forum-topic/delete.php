<?php

use App\Community\Models\ForumTopic;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($username, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

/** @var User $user */
$user = request()->user();

$input = Validator::validate(Arr::wrap(request()->post()), [
    'topic' => 'required|integer|exists:ForumTopic,ID',
]);

/** @var ForumTopic $topic */
$topic = ForumTopic::find($input['topic']);
$topic->delete();

return back()->with('success', __('legacy.success.delete'));
