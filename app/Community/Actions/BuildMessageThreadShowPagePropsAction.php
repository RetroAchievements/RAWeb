<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Data\MessageData;
use App\Community\Data\MessageThreadData;
use App\Community\Data\MessageThreadShowPagePropsData;
use App\Data\PaginatedData;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\User;
use App\Policies\MessageThreadPolicy;

class BuildMessageThreadShowPagePropsAction
{
    /**
     * @return array{props: ?MessageThreadShowPagePropsData, redirectToPage: ?int, redirectToMessage?: int}
     */
    public function execute(
        MessageThread $messageThread,
        User $user,
        int $currentPage = 1,
        int $perPage = 20,
        bool $wasPageExplicitlyRequested = true,
    ): array {
        // First, check if we need to redirect.
        $totalMessages = $messageThread->messages()->count();
        $lastPage = max(1, (int) ceil($totalMessages / $perPage));

        // If no page was explicitly requested and there are multiple pages, redirect to the last page.
        if (!$wasPageExplicitlyRequested && $currentPage === 1 && $lastPage > 1) {
            // Get the ID of the newest message to scroll to.
            $newestMessage = $messageThread->messages()
                ->orderBy('created_at', 'desc')
                ->first(['id']);

            return [
                'props' => null,
                'redirectToPage' => $lastPage,
                'redirectToMessage' => $newestMessage->id ?? null,
            ];
        }

        if ($currentPage !== 1 && $currentPage > $lastPage) {
            return ['props' => null, 'redirectToPage' => $lastPage];
        }

        // Only fetch the actual messages if we're not redirecting.
        $paginatedMessages = $messageThread->messages()
            ->with(['author', 'sentBy'])
            ->orderBy('created_at')
            ->paginate($perPage);

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
            eventIds: $entities['eventIds'],
            hubIds: $entities['hubIds'],
        );

        $accessibleTeamIds = (new MessageThreadPolicy())->getAccessibleTeamIds($user);

        // Finally, update the message bodies sent to the UI with the converted user shortcodes.
        $messages = $paginatedMessages->getCollection()->map(function ($message, $index) use ($updatedBodies, $accessibleTeamIds) {
            $message->body = $updatedBodies[$index];

            /**
             * Only include the sentBy value if the user is viewing as a team
             * account the user has access to. If we always naively include it,
             * Inertia will leak the value into the DOM as part of the hydration
             * process.
             */
            if ($message->sent_by_id !== null && in_array($message->author_id, $accessibleTeamIds, true)) {
                return MessageData::fromMessage($message)->include('author', 'sentBy');
            }

            return MessageData::fromMessage($message)->include('author');
        })->all();

        $senderUser = $this->getSenderUser($messageThread, $user);

        $props = new MessageThreadShowPagePropsData(
            messageThread: MessageThreadData::fromMessageThread($messageThread),
            paginatedMessages: PaginatedData::fromLengthAwarePaginator(
                $paginatedMessages,
                total: $paginatedMessages->total(),
                items: $messages,
            ),
            dynamicEntities: $dynamicEntities,
            canReply: $this->getCanReply($messageThread, $user),
            senderUserAvatarUrl: $senderUser->avatar_url,
            senderUserDisplayName: $senderUser->display_name,
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

    private function getSenderUser(MessageThread $thread, User $user): User
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

            return User::firstWhere('ID', $foundTeamParticipant->id);
        }

        return $user;
    }
}
