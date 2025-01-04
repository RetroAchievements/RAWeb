<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\AddToMessageThreadAction;
use App\Community\Actions\CreateMessageThreadAction;
use App\Community\Requests\MessageRequest;
use App\Http\Controller;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\User;
use App\Support\Shortcode\Shortcode;
use Illuminate\Http\RedirectResponse;

class MessageController extends Controller
{
    public function store(MessageRequest $request): RedirectResponse
    {
        $this->authorize('create', Message::class);

        /** @var User $user */
        $user = request()->user();

        $input = $request->validated();

        $body = $input['body'];
        $body = normalize_shortcodes($input['body']);
        $body = Shortcode::convertUserShortcodesToUseIds($body);

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

            foreach ($thread->users as $threadUser) {
                if (!$threadUser->is($user) && !$user->can('sendToRecipient', [Message::class, $threadUser])) {
                    return back()->withErrors($user->isMuted() ?
                        __('legacy.error.muted_user') :
                        __('legacy.error.cannot_message_user'));
                }
            }

            (new AddToMessageThreadAction())->execute($thread, $user, $body);
        } else {
            $recipient = User::whereName($input['recipient'])->first();

            if (!$user->can('sendToRecipient', [Message::class, $recipient])) {
                return back()->withErrors($user->isMuted() ?
                    __('legacy.error.muted_user') :
                    __('legacy.error.cannot_message_user'));
            }

            $thread = (new CreateMessageThreadAction())->execute($user, $recipient, $input['title'], $body);
        }

        return redirect(route("message-thread.show", ['messageThread' => $thread->id]))->with('success', __('legacy.success.message_send'));
    }
}
