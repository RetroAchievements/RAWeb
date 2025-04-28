<?php

declare(strict_types=1);

namespace App\Community\Controllers\Api;

use App\Community\Actions\AddToMessageThreadAction;
use App\Community\Actions\CreateMessageThreadAction;
use App\Community\Requests\MessageRequest;
use App\Http\Controller;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use App\Policies\MessageThreadPolicy;
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

        $authorId = $user->id;

        $thread = null;
        if (array_key_exists('thread_id', $input) && $input['thread_id'] !== null) {
            $thread = MessageThread::firstWhere('id', $input['thread_id']);
            if (!$thread) {
                return response()->json(['error' => 'thread_not_found'], 400);
            }

            $participants = $thread->participants;

            // Is the current user a participant? If not, they may be replying while
            // viewing a team account inbox they have permission to access.
            $isUserParticipant = $participants->contains('ID', $user->ID);
            if (!$isUserParticipant) {
                $policy = new MessageThreadPolicy();
                $accessibleTeamIds = $policy->getAccessibleTeamIds($user);

                if (empty($accessibleTeamIds)) {
                    return response()->json(['error' => 'not_participant'], 403);
                }

                // Check if any of those team accounts are participants.
                $foundTeamParticipant = $thread->participants()
                    ->whereIn('user_id', $accessibleTeamIds)
                    ->first();

                if (!$foundTeamParticipant) {
                    return response()->json(['error' => 'not_participant'], 403);
                }

                $authorId = $foundTeamParticipant->id;
            }

            $userFrom = User::firstWhere('ID', $authorId);

            foreach ($thread->users as $threadUser) {
                if (!$threadUser->is($userFrom) && !$userFrom->can('sendToRecipient', [Message::class, $threadUser])) {
                    return response()->json([
                        'error' => $userFrom->isMuted() ? 'muted_user' : 'cannot_message_user',
                        403,
                    ]);
                }
            }

            (new AddToMessageThreadAction())->execute($thread, $userFrom, $user, $body);
        } else {
            $recipient = User::whereName($input['recipient'])->first();

            // Check if we're trying to send as a team account.
            if (
                isset($input['senderUserDisplayName'])
                && !empty($input['senderUserDisplayName'])
                && $user->display_name !== $input['senderUserDisplayName']
            ) {
                $teamAccount = User::firstWhere('User', $input['senderUserDisplayName']);
                if ($teamAccount) {
                    // Verify via policy that the user can actually send from this team account.
                    $policy = new MessageThreadPolicy();
                    $canSend = $policy->create($user, $teamAccount);

                    if (!$canSend) {
                        return response()->json(['error' => 'cannot_message_user'], 403);
                    }

                    $authorId = $teamAccount->id;
                }
            }

            $userFrom = User::firstWhere('ID', $authorId);

            if (!$userFrom->can('sendToRecipient', [Message::class, $recipient])) {
                return response()->json([
                    'error' => $user->isMuted() ? 'muted_user' : 'cannot_message_user',
                    403,
                ]);
            }

            $thread = (new CreateMessageThreadAction())->execute($userFrom, $recipient, $user, $input['title'], $body);
        }

        return response()->json(['success' => true, 'threadId' => $thread->id]);
    }
}
