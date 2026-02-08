<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Data\MessageThreadData;
use App\Community\Data\MessageThreadIndexPagePropsData;
use App\Data\PaginatedData;
use App\Data\UserPermissionsData;
use App\Models\MessageThread;
use App\Models\User;
use App\Policies\MessageThreadPolicy;

class BuildMessageThreadIndexPagePropsAction
{
    /**
     * @return array{props: ?MessageThreadIndexPagePropsData, redirectToPage: ?int}
     */
    public function execute(
        User $inboxUser,
        User $me,
        int $currentPage = 1,
        int $perPage = 20,
    ): array {
        $messageThreadsQuery = MessageThread::query()
            ->select([
                'message_threads.*',
                'messages.author_id as last_author_id',
                'messages.created_at as last_message_at',
            ])
            ->join('message_thread_participants', 'message_thread_participants.thread_id', '=', 'message_threads.id')
            ->join('messages', 'messages.id', '=', 'message_threads.last_message_id')
            ->where('message_thread_participants.user_id', $inboxUser->id)
            ->whereNull('message_thread_participants.deleted_at')
            ->with([
                'messages',
                'lastMessage',
                'messages.author' => function ($query) {
                    $query->withTrashed();
                },
                'participants' => function ($query) {
                    $query->withTrashed();
                },
            ])
            ->orderBy('messages.created_at', 'desc');

        // Get total message threads to calculate the last page.
        $totalMessageThreads = $messageThreadsQuery->count();
        $lastPage = (int) ceil($totalMessageThreads / $perPage);

        // If we're past the last page, indicate we need to redirect.
        if ($currentPage !== 1 && $currentPage > $lastPage) {
            return ['props' => null, 'redirectToPage' => $lastPage];
        }

        $paginatedMessageThreads = $messageThreadsQuery->paginate($perPage);

        $props = new MessageThreadIndexPagePropsData(
            can: UserPermissionsData::fromUser($inboxUser)->include('createMessageThreads'),
            paginatedMessageThreads: PaginatedData::fromLengthAwarePaginator(
                $paginatedMessageThreads,
                total: $paginatedMessageThreads->total(),
                items: MessageThreadData::fromCollection($paginatedMessageThreads->getCollection())
            ),
            unreadMessageCount: $inboxUser->unread_messages ?? 0,
            senderUserDisplayName: $inboxUser->display_name,
            selectableInboxDisplayNames: $this->getAccessibleInboxes($me),
        );

        return ['props' => $props, 'redirectToPage' => null];
    }

    private function getAccessibleInboxes(User $user): array
    {
        $policy = new MessageThreadPolicy();
        $accessibleTeamUsernames = $policy->getAccessibleTeamUsernames($user);

        return [$user->display_name, ...$accessibleTeamUsernames];
    }
}
