<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Data\MessageData;
use App\Community\Data\MessageThreadData;
use App\Community\Data\MessageThreadShowPagePropsData;
use App\Data\PaginatedData;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\User;
use App\Policies\MessageThreadPolicy;

class BuildMessageThreadShowPagePropsAction
{
    /**
     * @return array{props: ?MessageThreadShowPagePropsData, redirectToPage: ?int}
     */
    public function execute(
        MessageThread $messageThread,
        User $user,
        int $currentPage = 1,
        int $perPage = 20,
    ): array {
        $paginatedMessages = $messageThread->messages()
            ->with('author')
            ->orderBy('created_at')
            ->paginate($perPage);

        $totalMessages = $paginatedMessages->total();
        $lastPage = (int) ceil($totalMessages / $perPage);

        if ($currentPage !== 1 && $currentPage > $lastPage) {
            return ['props' => null, 'redirectToPage' => $lastPage];
        }

        // If we're viewing the last page, mark all messages in the thread as read.
        if ($currentPage === $lastPage) {
            (new ReadMessageThreadAction())->execute($messageThread, $user);
        }

        // Extract the message bodies for processing before they're sent to the UI.
        $messageBodies = $paginatedMessages->getCollection()->pluck('body')->all();

        // Convert user ID shortcodes to use display names.
        $updatedBodies = (new ConvertUserShortcodesFromIdsToDisplayNamesAction())->execute($messageBodies);

        // Extract all dynamic entities from the updated bodies.
        $entities = (new ExtractDynamicShortcodeEntitiesAction())->execute($updatedBodies);

        // Fetch all dynamic content so it can be performantly hydrated in the UI.
        $dynamicEntities = (new FetchDynamicShortcodeContentAction())->execute(
            usernames: $entities['usernames'],
            ticketIds: $entities['ticketIds'],
            achievementIds: $entities['achievementIds'],
            gameIds: $entities['gameIds'],
            hubIds: $entities['hubIds'],
        );

        // Finally, update the message bodies sent to the UI with the converted user shortcodes.
        $messages = $paginatedMessages->getCollection()->map(function ($message, $index) use ($updatedBodies) {
            $message->body = $updatedBodies[$index];

            return MessageData::fromMessage($message)->include('author');
        })->all();

        $props = new MessageThreadShowPagePropsData(
            messageThread: MessageThreadData::fromMessageThread($messageThread),
            paginatedMessages: PaginatedData::fromLengthAwarePaginator(
                $paginatedMessages,
                total: $paginatedMessages->total(),
                items: $messages,
            ),
            dynamicEntities: $dynamicEntities,
            canReply: $this->getCanReply($messageThread, $user),
            senderUserDisplayName: $this->getSenderUserDisplayName($messageThread, $user),
        );

        return ['props' => $props, 'redirectToPage' => null];
    }

    private function getCanReply(MessageThread $messageThread, User $user): bool
    {
        $participants = MessageThreadParticipant::withTrashed()
            ->where('thread_id', $messageThread->id)
            ->join('UserAccounts', 'UserAccounts.ID', '=', 'message_thread_participants.user_id');

        $canReply = ($participants->count() === 1) || (clone $participants)
            ->where('user_id', '!=', $user->id)
            ->whereNull('UserAccounts.Deleted')
            ->exists();

        return $canReply;
    }

    private function getSenderUserDisplayName(MessageThread $thread, User $user): string
    {
        $isUserParticipant = $thread->participants->contains('ID', $user->id);
        if (!$isUserParticipant) {
            $policy = new MessageThreadPolicy();
            $accessibleTeamIds = $policy->getAccessibleTeamIds($user);

            if (empty($accessibleTeamIds)) {
                return $user->display_name;
            }

            $foundTeamParticipant = $thread->participants()
                ->whereIn('user_id', $accessibleTeamIds)
                ->first();

            if (!$foundTeamParticipant) {
                return $user->display_name;
            }

            return User::firstWhere('ID', $foundTeamParticipant->id)->display_name;
        }

        return $user->display_name;
    }
}
