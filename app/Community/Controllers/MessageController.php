<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Models\Message;
use App\Http\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $messages = Message::where('recipient_id', $request->user()->id)
            ->orWhere('sender_id', $request->user()->id)
            ->with(['sender', 'recipient'])
            ->orderByDesc('sent_at')
            ->paginate();

        return view('message.index')
            ->with('messages', $messages);
    }

    public function create(): View
    {
        return view('message.create');
    }

    public function store(Request $request): void
    {
    }

    public function show(Message $message): void
    {
    }

    public function edit(Message $message): void
    {
    }

    public function update(Request $request, Message $message): void
    {
    }

    public function destroy(Message $message): void
    {
    }
}
