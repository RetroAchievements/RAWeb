<?php

declare(strict_types=1);

namespace App\Community\Controllers\Api;

use App\Community\Actions\AddToMessageThreadAction;
use App\Community\Actions\CreateMessageThreadAction;
use App\Community\Requests\MessageRequest;
use App\Http\Controller;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\User;
use App\Support\Shortcode\Shortcode;
use Illuminate\Http\JsonResponse;

class MessageApiController extends Controller
{
    public function store(MessageRequest $request): JsonResponse
    {
        $this->authorize('create', Message::class);

        /** @var User $user */
        $user = $request->user();

        $input = $request->validated();

        $body = $input['body'];
        $body = normalize_shortcodes($input['body']);
        $body = Shortcode::convertUserShortcodesToUseIds($body);

        if (array_key_exists('thread_id', $input) && $input['thread_id'] !== null) {
            $thread = MessageThread::firstWhere('id', $input['thread_id']);
            if (!$thread) {
                return response()->json(['error' => 'thread_not_found'], 400);
            }

            $participant = MessageThreadParticipant::withTrashed()
                ->where('thread_id', $input['thread_id'])
                ->where('user_id', $user->id);
            if (!$participant->exists()) {
                return response()->json(['error' => 'not_participant'], 403);
            }

            foreach ($thread->users as $threadUser) {
                if (!$threadUser->is($user) && !$user->can('sendToRecipient', [Message::class, $threadUser])) {
                    return response()->json([
                        'error' => $user->isMuted() ? 'muted_user' : 'cannot_message_user',
                        403,
                    ]);
                }
            }

            (new AddToMessageThreadAction())->execute($thread, $user, $body);
        } else {
            $recipient = User::whereName($input['recipient'])->first();

            if (!$user->can('sendToRecipient', [Message::class, $recipient])) {
                return response()->json([
                    'error' => $user->isMuted() ? 'muted_user' : 'cannot_message_user',
                    403,
                ]);
            }

            $thread = (new CreateMessageThreadAction())->execute($user, $recipient, $input['title'], $body);
        }

        return response()->json(['success' => true]);
    }
}
