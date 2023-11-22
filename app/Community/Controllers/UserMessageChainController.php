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
            // TODO: abort(404);
        }

        $pageSize = 20;
        $currentPage = (int) $request->input('page.number') ?? 1;
        if ($currentPage < 1) {
            $currentPage = 1;
        }
        $totalPages = (int) (($messageChain->num_messages + 19) / 20);

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
}
