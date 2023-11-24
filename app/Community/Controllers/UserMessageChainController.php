<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Enums\TicketState;
use App\Community\Enums\UserRelationship;
use App\Community\Models\Ticket;
use App\Community\Models\UserMessage;
use App\Community\Models\UserMessageChain;
use App\Community\Models\UserRelation;
use App\Http\Controller;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerGame;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserMessageChainController extends Controller
{
    public function __invoke(Request $request): View
    {
        $chainId = $request->route('chain');

        $messageChain = UserMessageChain::firstWhere('id', $chainId);
        if (!$messageChain) {
            abort(404);
        }

        $user = $request->user();
        if ($messageChain->sender_id != $user->id && $messageChain->recipient_id != $user->id) {
            abort(404);
        }

        $pageSize = 20;
        $currentPage = (int) $request->input('page.number') ?? 1;
        if ($currentPage < 1) {
            $currentPage = 1;
        }
        $totalPages = (int) (($messageChain->num_messages + 19) / 20);

        if ($currentPage == $totalPages) {
            // if viewing last page, mark all messages in the chain as read
            UserMessageChainController::markRead($messageChain, $user);
        }

        $messages = UserMessage::where('chain_id', $chainId)
            ->offset(($currentPage - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        return view('community.components.message.view-chain-page', [
            'messageChain' => $messageChain,
            'messages' => $messages,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
        ]);
    }

    public function pageCreate(Request $request): View
    {
        $toUser = $request->input('to') ?? '';

        return view('community.components.message.new-chain-page', [
            'toUser' => $toUser
        ]);
    }

    public static function newChain(User $userFrom, User $userTo, string $title, string $body): UserMessageChain
    {
        $userMessageChain = new UserMessageChain([
            'title' => $title,
            'sender_id' => $userFrom->ID,
            'recipient_id' => $userTo->ID,
        ]);

        UserMessageChainController::addToChain($userMessageChain, $userFrom, $body);
        return $userMessageChain;
    }

    public static function addToChain(UserMessageChain $userMessageChain, User $userFrom, string $body): void
    {
        $now = Carbon::now();

        $userMessageChain->num_messages++;
        if ($userMessageChain->recipient_id == $userFrom->ID) {
            $userMessageChain->recipient_last_post_at = $now;

            $relationship = UserRelation::getRelationship($userMessageChain->sender->User, $userFrom->User);
            if ($relationship == UserRelationship::Blocked) {
                $userMessageChain->sender_num_unread = 0;
                $userMessageChain->sender_deleted_at = $now;
            } else {
                $userMessageChain->sender_num_unread++;
                $userMessageChain->sender_deleted_at = null;
            }

            $userTo = User::firstWhere('id', $userMessageChain->sender_id);
        } else {
            $userMessageChain->sender_last_post_at = $now;

            $relationship = UserRelation::getRelationship($userMessageChain->recipient->User, $userFrom->User);
            if ($relationship == UserRelationship::Blocked) {
                $userMessageChain->recipient_num_unread = 0;
                $userMessageChain->recipient_deleted_at = $now;
            } else {
                $userMessageChain->recipient_num_unread++;
                $userMessageChain->recipient_deleted_at = null;
            }

            $userTo = User::firstWhere('id', $userMessageChain->recipient_id);
        }
        $userMessageChain->save();
        
        $userMessage = new UserMessage([
            'chain_id' => $userMessageChain->id,
            'author_id' => $userFrom->ID,
            'body' => $body,
        ]);
        $userMessage->save();
        
        UserMessageChainController::updateUnreadMessageCount($userTo);

        // TODO: send email
    }

    private static function updateUnreadMessageCount(User $user): void
    {
        $unreadReplies = UserMessageChain::where('sender_id', $user->id)
            ->whereNull('sender_deleted_at')
            ->sum('sender_num_unread');

        $unreadMessages = UserMessageChain::where('recipient_id', $user->id)
            ->whereNull('recipient_deleted_at')
            ->sum('recipient_num_unread');

        $user->UnreadMessageCount = $unreadMessages + $unreadReplies;
        $user->save();
    }

    public static function markRead(UserMessageChain $userMessageChain, User $user): void
    {
        if ($userMessageChain->recipient_id == $user->ID) {
            if ($userMessageChain->recipient_num_unread) {
                $userMessageChain->recipient_num_unread = 0;
                $userMessageChain->save();

                UserMessageChainController::updateUnreadMessageCount($user);
            }
        } else {
            if ($userMessageChain->sender_num_unread) {
                $userMessageChain->sender_num_unread = 0;
                $userMessageChain->save();

                UserMessageChainController::updateUnreadMessageCount($user);
            }
        }
    }

    public static function deleteChain(UserMessageChain $userMessageChain, User $user): void
    {
        $now = Carbon::now();

        if ($userMessageChain->recipient_id == $user->ID) {
            $userMessageChain->recipient_num_unread = 0;
            $userMessageChain->recipient_deleted_at = $now;
        } else {
            $userMessageChain->sender_num_unread = 0;
            $userMessageChain->sender_deleted_at = $now;
        }

        $userMessageChain->save();
        
        // TODO: hard delete if both deleted_at fields are not null?

        UserMessageChainController::updateUnreadMessageCount($user);
    }
}
