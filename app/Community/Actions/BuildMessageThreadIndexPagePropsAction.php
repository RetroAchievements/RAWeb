<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Data\MessageThreadData;
use App\Community\Data\MessageThreadIndexPagePropsData;
use App\Data\PaginatedData;
use App\Models\MessageThread;
use App\Models\User;

class BuildMessageThreadIndexPagePropsAction
{
    /**
     * @return array{props: ?MessageThreadIndexPagePropsData, redirectToPage: ?int}
     */
    public function execute(
        User $user,
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
            ->where('message_thread_participants.user_id', $user->id)
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
            paginatedMessageThreads: PaginatedData::fromLengthAwarePaginator(
                $paginatedMessageThreads,
                total: $paginatedMessageThreads->total(),
                items: MessageThreadData::fromCollection($paginatedMessageThreads->getCollection())
            ),
            unreadMessageCount: $user->UnreadMessageCount,
        );

        return ['props' => $props, 'redirectToPage' => null];
    }
}
