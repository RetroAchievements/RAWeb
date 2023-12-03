<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\AddToMessageThreadAction;
use App\Community\Actions\CreateMessageThreadAction;
use App\Community\Models\MessageThread;
use App\Community\Models\MessageThreadParticipant;
use App\Http\Controller;
use App\Site\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    public function create(Request $request): View
    {
        $toUser = $request->input('to') ?? '';
        $subject = $request->input('subject') ?? '';
        $message = $request->input('message') ?? '';

        return view('community.message.create', [
            'toUser' => $toUser,
            'subject' => $subject,
            'message' => $message,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
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

            $participant = MessageThreadParticipant::withTrashed()
                ->where('thread_id', $input['thread_id'])
                ->where('user_id', $user->ID);
            if (!$participant->exists()) {
                return back()->withErrors(__('legacy.error.error'));
            }

            (new AddToMessageThreadAction())->execute($thread, $user, $input['body']);
        } else {
            $recipient = User::firstWhere('User', $input['recipient']);
            $thread = (new CreateMessageThreadAction())->execute($user, $recipient, $input['title'], $input['body']);
        }

        return redirect(route("message-thread.show", $thread->id))->with('success', __('legacy.success.message_send'));
    }
}
