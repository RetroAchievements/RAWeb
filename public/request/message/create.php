<?php

use App\Community\Controllers\MessageThreadsController;
use App\Community\Models\MessageThread;
use App\Community\Models\MessageThreadParticipant;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetail) || $permissions < Permissions::Registered) {
    return back()->withErrors(__('legacy.error.permissions'));
}

/** @var User $user */
$user = request()->user();

$input = Validator::validate(Arr::wrap(request()->post()), [
    'thread_id' => 'nullable|integer',
    'body' => 'required|string|max:60000',
    'title' => 'required_without:thread_id|string|max:255',
    'recipient' => 'required_without:thread_id|exists:UserAccounts,User',
]);

if (array_key_exists('thread_id', $input) && $input['thread_id'] != null) {
    $thread = MessageThread::firstWhere('id', $input['thread_id']);
    if (!$thread) {
        return back()->withErrors(__('legacy.error.error'));
    }
    $participant = MessageThreadParticipant::where('thread_id', $input['thread_id'])
        ->where('user_id', $user->ID);
    if (!$participant->exists()) {
        return back()->withErrors(__('legacy.error.error'));
    }
    MessageThreadsController::addToThread($thread, $user, $input['body']);
} else {
    $recipient = User::firstWhere('User', $input['recipient']);
    $thread = MessageThreadsController::newThread($user, $recipient, $input['title'], $input['body']);
}

return redirect(route("message.view", $thread->id))->with('success', __('legacy.success.message_send'));
