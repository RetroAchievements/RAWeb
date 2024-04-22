<?php

use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($username, $permissions, $userDetails)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'topic' => 'required|integer|exists:ForumTopic,ID',
]);

/** @var ForumTopic $topic */
$topic = ForumTopic::find($input['topic']);

$userModel = User::firstWhere('User', $username);

if (!$userModel->can('delete', $topic)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$topic->delete();

return back()->with('success', __('legacy.success.delete'));
