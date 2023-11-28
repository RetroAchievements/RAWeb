<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\ReadMessageThreadAction;
use App\Community\Events\MessageCreated;
use App\Community\Models\Message;
use App\Community\Models\MessageThread;
use App\Community\Models\MessageThreadParticipant;
use App\Http\Controller;
use App\Site\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MessageThreadsController extends Controller
{
    public function pageList(Request $request): View
    {
        $user = $request->user();

        $messageThreads = MessageThread::join('message_thread_participants', 'message_thread_participants.thread_id', '=', 'message_threads.id')
            ->where('message_thread_participants.user_id', $user->ID)
            ->whereNull('message_thread_participants.deleted_at');
        $totalMessages = $messageThreads->count();

        $pageSize = 20;
        $currentPage = (int) ($request->input('page.number') ?? 1);
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

        return view('community.components.message.list-page', [
            'messages' => $messageThreads,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
            'unreadCount' => $request->user()->UnreadMessageCount,
            'totalMessages' => $totalMessages,
        ]);
    }

    public function pageView(Request $request): View
    {
        $threadId = $request->route('thread_id');

        $thread = MessageThread::firstWhere('id', $threadId);
        if (!$thread) {
            abort(404);
        }

        $user = $request->user();
        $participant = MessageThreadParticipant::where('thread_id', $threadId)
            ->where('user_id', $user->ID)
            ->first();
        if (!$participant) {
            abort(404);
        }

        $pageSize = 20;
        $currentPage = (int) ($request->input('page.number') ?? 1);
        if ($currentPage < 1) {
            $currentPage = 1;
        }
        $totalPages = (int) (($thread->num_messages + 19) / 20);

        if ($currentPage == $totalPages) {
            // if viewing last page, mark all messages in the chain as read
            ReadMessageThreadAction::markParticipantRead($participant, $user);
        }

        $messages = Message::where('thread_id', $threadId)
            ->orderBy('created_at')
            ->offset(($currentPage - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        $participants = MessageThreadParticipant::withTrashed()
            ->where('thread_id', $thread->id)
            ->join('UserAccounts', 'UserAccounts.ID', '=', 'message_thread_participants.user_id');

        $canReply = (clone $participants)
            ->where('user_id', '!=', $user->ID)
            ->whereNull('UserAccounts.Deleted')
            ->exists();

        $participants = $participants->get(['UserAccounts.ID', 'UserAccounts.User'])
            ->mapWithKeys(function ($participant, $key) {
                return [$participant->ID => $participant->User];
            })
            ->toArray();

        return view('community.components.message.view-thread-page', [
            'thread' => $thread,
            'messages' => $messages,
            'participants' => $participants,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
            'canReply' => $canReply,
        ]);
    }

    public function pageCreate(Request $request): View
    {
        $toUser = $request->input('to') ?? '';
        $subject = $request->input('subject') ?? '';
        $message = $request->input('message') ?? '';

        return view('community.components.message.new-thread-page', [
            'toUser' => $toUser,
            'subject' => $subject,
            'message' => $message,
        ]);
    }

    public static function newThread(User $userFrom, User $userTo, string $title, string $body, bool $isProxied = false): MessageThread
    {
        $thread = new MessageThread([
            'title' => $title,
        ]);
        $thread->save();

        $participantFrom = new MessageThreadParticipant([
            'user_id' => $userFrom->ID,
            'thread_id' => $thread->id,
        ]);

        if ($isProxied) {
            $participantFrom->deleted_at = Carbon::now();
        }

        $participantFrom->save();

        if ($userTo->ID != $userFrom->ID) {
            $participantTo = new MessageThreadParticipant([
                'user_id' => $userTo->ID,
                'thread_id' => $thread->id,
            ]);

            // if the recipient has blocked the sender, immediately mark the thread as deleted for the recipient
            if ($userTo->isBlocking($userFrom->User)) {
                $participantTo->deleted_at = Carbon::now();
            }

            $participantTo->save();
        }

        MessageThreadsController::addToThread($thread, $userFrom, $body);

        return $thread;
    }

    public static function addToThread(MessageThread $thread, User $userFrom, string $body): void
    {
        $message = new Message([
            'thread_id' => $thread->id,
            'author_id' => $userFrom->ID,
            'body' => $body,
            'created_at' => Carbon::now(),
        ]);
        $message->save();

        $thread->num_messages++;
        $thread->last_message_id = $message->id;
        $thread->save();

        MessageCreated::dispatch($message);
    }
}
