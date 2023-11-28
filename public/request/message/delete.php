<?php

use App\Community\Actions\DeleteMessageThreadAction;
use App\Community\Controllers\MessageThreadsController;
use App\Community\Models\MessageThread;
use App\Community\Models\MessageThreadParticipant;
use App\Site\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'thread_id' => 'required|integer|exists:message_threads,id',
]);

$thread = MessageThread::firstWhere('id', $input['thread_id']);
if (!$thread) {
    return back()->withErrors(__('legacy.error.error'));
}

/** @var User $user */
$user = request()->user();

$participating = MessageThreadParticipant::where('thread_id', $input['thread_id'])
    ->where('user_id', $user->ID)
    ->exists();

if (!$participating) {
    return back()->withErrors(__('legacy.error.error'));
}

(new DeleteMessageThreadAction)->execute($thread, $user);

return redirect(route("message.list"))->with('success', __('legacy.success.message_delete'));
