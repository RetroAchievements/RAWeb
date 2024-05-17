<?php

declare(strict_types=1);

namespace App\Components;

use App\Community\Enums\TicketState;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TicketNotificationsIcon extends Component
{
    public ?string $class = null;

    public function render(): View
    {
        /** @var User $user */
        $user = request()->user();

        $notifications = collect();

        // Open ticket notifications
        $openTicketsData = countOpenTicketsByDev($user);
        if ($openTicketsData[TicketState::Open]) {
            $notifications->push([
                'link' => route('developer.tickets', ['user' => $user]),
                'title' => $openTicketsData[TicketState::Open] . ' ' . __res('ticket', (int) $openTicketsData[TicketState::Open]) . ' for you to resolve',
                'class' => 'text-danger',
            ]);
        }
        if ($openTicketsData[TicketState::Request]) {
            $notifications->push([
                'link' => route('developer.tickets', ['user' => $user]),
                'title' => $openTicketsData[TicketState::Request] . ' ' . __res('ticket', (int) $openTicketsData[TicketState::Request]) . ' pending feedback',
                'read' => true,
            ]);
        }

        $ticketFeedback = countRequestTicketsByUser($user);
        if ($ticketFeedback) {
            $notifications->push([
                'link' => route('reporter.tickets', $user),
                'title' => $ticketFeedback . ' ' . __res('ticket', $ticketFeedback) . ' awaiting your feedback',
            ]);
        }

        $unreadCount = $notifications->filter(fn ($notification) => !($notification['read'] ?? false))->count();

        return view('components.notifications.ticket')
            ->with('notifications', $notifications)
            ->with('count', $unreadCount);
    }
}
