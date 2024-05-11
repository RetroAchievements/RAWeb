<?php

declare(strict_types=1);

namespace App\Community\Services;

use App\Community\Actions\ReadMessageThreadAction;
use App\Enums\UserPreference;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MessageThreadService
{
    public function buildForMessageThreadsIndexViewData(User $user, int $forPageNumber = 1): array
    {
        $messageThreads = MessageThread::join('message_thread_participants', 'message_thread_participants.thread_id', '=', 'message_threads.id')
            ->where('message_thread_participants.user_id', $user->ID)
            ->whereNull('message_thread_participants.deleted_at');
        $totalMessages = $messageThreads->count();

        $pageSize = 20;
        $currentPage = $forPageNumber;
        if ($currentPage < 1) {
            $currentPage = 1;
        }
        $totalPages = (int) (($totalMessages + 19) / 20);

        $messageThreads = $messageThreads
            ->join('messages', 'messages.id', '=', 'message_threads.last_message_id')
            ->orderBy('messages.created_at', 'DESC')
            ->offset(($currentPage - 1) * $pageSize)
            ->limit($pageSize)
            ->select(['message_threads.*',
                DB::raw('messages.author_id AS last_author_id'),
                DB::raw('messages.created_at AS last_message_at'),
            ])
            ->get();

        foreach ($messageThreads as &$messageThread) {
            $messageThread['other_participants'] = User::withTrashed()
                ->join('message_thread_participants', 'message_thread_participants.user_id', '=', 'UserAccounts.ID')
                ->where('message_thread_participants.thread_id', '=', $messageThread->id)
                ->where('message_thread_participants.user_id', '!=', $user->ID)
                ->pluck('UserAccounts.User')
                ->toArray();
            $messageThread['num_unread'] = MessageThreadParticipant::where('user_id', $user->ID)
                ->where('thread_id', $messageThread->id)
                ->value('num_unread');
        }

        return [
            'currentPage' => $currentPage,
            'isShowAbsoluteDatesPreferenceSet' => BitSet($user->websitePrefs, UserPreference::Forum_ShowAbsoluteDates),
            'messages' => $messageThreads,
            'monthAgo' => Carbon::now()->subMonth(1),
            'totalMessages' => $totalMessages,
            'totalPages' => $totalPages,
            'unreadCount' => $user->UnreadMessageCount,
            'user' => $user,
        ];
    }

    public function buildForMessageThreadViewData(
        User $user,
        MessageThread $messageThread,
        int $forPageNumber = 1,
    ): array {
        $participant = MessageThreadParticipant::where('thread_id', $messageThread->id)
            ->where('user_id', $user->ID)
            ->first();
        if (!$participant) {
            abort(404);
        }

        $pageSize = 20;
        $currentPage = $forPageNumber;
        if ($currentPage < 1) {
            $currentPage = 1;
        }
        $totalPages = (int) (($messageThread->num_messages + 19) / 20);

        if ($currentPage == $totalPages) {
            // if viewing last page, mark all messages in the chain as read
            ReadMessageThreadAction::markParticipantRead($participant, $user);
        }

        $messages = Message::where('thread_id', $messageThread->id)
            ->orderBy('created_at')
            ->offset(($currentPage - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        $participants = MessageThreadParticipant::withTrashed()
            ->where('thread_id', $messageThread->id)
            ->join('UserAccounts', 'UserAccounts.ID', '=', 'message_thread_participants.user_id');

        $canReply = ($participants->count() === 1) || (clone $participants)
            ->where('user_id', '!=', $user->ID)
            ->whereNull('UserAccounts.Deleted')
            ->exists();

        $participants = $participants->get(['UserAccounts.ID', 'UserAccounts.User'])
            ->mapWithKeys(function ($participant, $key) {
                return [$participant->ID => $participant->User];
            })
            ->toArray();

        $isShowAbsoluteDatesPreferenceSet = BitSet(request()->user()->websitePrefs, UserPreference::Forum_ShowAbsoluteDates);
        $monthAgo = Carbon::now()->subMonth(1);
        
        $participantModels = [];
        if (empty($participants)) {
            foreach ($messages as $message) {
                if (!array_key_exists($message->author_id, $participants)) {
                    $participantModel = User::withTrashed()->firstWhere('id', $message->author_id);
                    if ($participantModel) {
                        $participantModels[$message->author_id] = $participantModel;
                        $participants[$participantModel->ID] = $participantModel->User;
                    }
                }
            }
        } else {
            foreach ($participants as $id => $participant) {
                $participantModel = User::withTrashed()->firstWhere('id', $id);
                if ($participantModel) {
                    $participantModels[$id] = $participantModel;
                }
            }
        }
        
        $pageDescription = "Conversation between " . implode(' and ', $participants);

        return [
            'canReply' => $canReply,
            'currentPage' => $currentPage,
            'isShowAbsoluteDatesPreferenceSet' => $isShowAbsoluteDatesPreferenceSet,
            'messages' => $messages,
            'messageThread' => $messageThread,
            'monthAgo' => $monthAgo,
            'pageDescription' => $pageDescription,
            'participants' => $participants,
            'totalPages' => $totalPages,
        ];
    }
}
