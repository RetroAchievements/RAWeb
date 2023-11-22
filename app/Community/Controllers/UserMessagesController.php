<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Enums\TicketState;
use App\Community\Models\Ticket;
use App\Community\Models\UserMessage;
use App\Community\Models\UserMessageChain;
use App\Http\Controller;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerGame;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserMessagesController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $respondedMessages = UserMessageChain::where('sender_id', $user->id)
            ->whereNull('sender_deleted_at')
            ->whereNotNull('recipient_last_post_at');

        $receivedMessages = UserMessageChain::where('recipient_id', $user->id)
            ->whereNull('recipient_deleted_at');

        $messages = $respondedMessages->union($receivedMessages);
        $totalMessages = $messages->count();

        $pageSize = 20;
        $currentPage = (int) $request->input('page.number') ?? 1;
        if ($currentPage < 1) {
            $currentPage = 1;
        }
        $totalPages = (int) (($totalMessages + 19) / 20);

        $messages = $messages
            ->orderBy(DB::raw(greatestStatement(['COALESCE(recipient_last_post_at,0)', 'sender_last_post_at'])), 'DESC')
            ->offset(($currentPage - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        return view('community.components.message.list-page', [
            'messages' => $messages,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
            'unreadCount' => $user->UnreadMessageCount,
            'totalMessages' => $totalMessages,
        ]);
    }
}
