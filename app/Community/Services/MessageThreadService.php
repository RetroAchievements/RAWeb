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
            ->where('message_thread_participants.user_id', $user->id)
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
                ->join('message_thread_participants', 'message_thread_participants.user_id', '=', DB::raw('users.id'))
                ->where('message_thread_participants.thread_id', '=', $messageThread->id)
                ->where('message_thread_participants.user_id', '!=', $user->id)
                ->pluck(DB::raw('users.username'))
                ->toArray();
            $messageThread['num_unread'] = MessageThreadParticipant::where('user_id', $user->id)
                ->where('thread_id', $messageThread->id)
                ->value('num_unread');
        }

        return [
            'currentPage' => $currentPage,
            'isShowAbsoluteDatesPreferenceSet' => BitSet($user->preferences_bitfield, UserPreference::Forum_ShowAbsoluteDates),
            'messages' => $messageThreads,
            'monthAgo' => Carbon::now()->subMonth(),
            'totalMessages' => $totalMessages,
            'totalPages' => $totalPages,
            'unreadCount' => $user->unread_messages,
            'user' => $user,
        ];
    }

    public function buildForMessageThreadViewData(
        User $user,
        MessageThread $messageThread,
        int $forPageNumber = 1,
    ): array {
        $participant = MessageThreadParticipant::where('thread_id', $messageThread->id)
            ->where('user_id', $user->id)
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
            ->join('users', DB::raw('users.id'), '=', 'message_thread_participants.user_id');

        $canReply = ($participants->count() === 1) || (clone $participants)
            ->where('user_id', '!=', $user->id)
            ->whereNull(DB::raw('users.deleted_at'))
            ->exists();

        $participants = $participants->get([DB::raw('users.id'), DB::raw('users.username')])
            ->mapWithKeys(function ($participant, $key) {
                return [$participant->id => $participant->username];
            })
            ->toArray();

        $isShowAbsoluteDatesPreferenceSet = BitSet(request()->user()->preferences_bitfield, UserPreference::Forum_ShowAbsoluteDates);
        $monthAgo = Carbon::now()->subMonth();

        $participantModels = [];
        if (empty($participants)) {
            foreach ($messages as $message) {
                if (!array_key_exists($message->author_id, $participants)) {
                    $participantModel = User::withTrashed()->firstWhere('id', $message->author_id);
                    if ($participantModel) {
                        $participantModels[$message->author_id] = $participantModel;
                        $participants[$participantModel->id] = $participantModel->username;
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
