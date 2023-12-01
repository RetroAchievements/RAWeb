<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\AddToMessageThreadAction;
use App\Community\Actions\CreateMessageThreadAction;
use App\Community\Actions\DeleteMessageThreadAction;
use App\Community\Actions\ReadMessageThreadAction;
use App\Community\Models\Message;
use App\Community\Models\MessageThread;
use App\Community\Models\MessageThreadParticipant;
use App\Http\Controller;
use App\Site\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MessageThreadController extends Controller
{
    public function index(Request $request): View
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

        return view('community.message-thread.index', [
            'messages' => $messageThreads,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
            'unreadCount' => $request->user()->UnreadMessageCount,
            'totalMessages' => $totalMessages,
        ]);
    }

    public function show(Request $request, int $threadId): View
    {
        $thread = MessageThread::firstWhere('id', $threadId);
        if (!$thread) {
            abort(404);
        }

        $user = $request->user();
        $participant = MessageThreadParticipant::where('thread_id', $thread->id)
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

        $messages = Message::where('thread_id', $thread->id)
            ->orderBy('created_at')
            ->offset(($currentPage - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        $participants = MessageThreadParticipant::withTrashed()
            ->where('thread_id', $thread->id)
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

        return view('community.message-thread.show', [
            'thread' => $thread,
            'messages' => $messages,
            'participants' => $participants,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
            'canReply' => $canReply,
        ]);
    }

    public function destroy(Request $request, int $threadId): RedirectResponse
    {
        $thread = MessageThread::firstWhere('id', $threadId);
        if (!$thread) {
            return back()->withErrors(__('legacy.error.error'));
        }

        /** @var User $user */
        $user = request()->user();

        $participating = MessageThreadParticipant::where('thread_id', $threadId)
            ->where('user_id', $user->ID)
            ->exists();

        if (!$participating) {
            return back()->withErrors(__('legacy.error.error'));
        }

        (new DeleteMessageThreadAction())->execute($thread, $user);

        return redirect(route('message-thread.index'))->with('success', __('legacy.success.message_delete'));
    }
}
