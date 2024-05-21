<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\DeleteMessageThreadAction;
use App\Http\Controller;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessageThreadController extends Controller
{
    public function destroy(Request $request, MessageThread $messageThread): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $participating = MessageThreadParticipant::where('thread_id', $messageThread->id)
            ->where('user_id', $user->ID)
            ->exists();

        if (!$participating) {
            return back()->withErrors(__('legacy.error.error'));
        }

        (new DeleteMessageThreadAction())->execute($messageThread, $user);

        return redirect(route('message-thread.index'))->with('success', __('legacy.success.message_delete'));
    }
}
