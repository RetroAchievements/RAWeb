<?php

declare(strict_types=1);

namespace App\Components;

use App\Community\Enums\TriggerTicketState;
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
        if ($openTicketsData[TriggerTicketState::Open->value]) {
            $notifications->push([
                'link' => route('developer.tickets', ['user' => $user->display_name]),
                'title' => $openTicketsData[TriggerTicketState::Open->value] . ' ' . __res('ticket', (int) $openTicketsData[TriggerTicketState::Open->value]) . ' for you to resolve',
                'class' => 'text-danger',
            ]);
        }
        if ($openTicketsData[TriggerTicketState::Request->value]) {
            $notifications->push([
                'link' => route('developer.tickets', ['user' => $user->display_name]),
                'title' => $openTicketsData[TriggerTicketState::Request->value] . ' ' . __res('ticket', (int) $openTicketsData[TriggerTicketState::Request->value]) . ' pending feedback',
                'read' => true,
            ]);
        }

        $ticketFeedback = countRequestTicketsByUser($user);
        if ($ticketFeedback) {
            $notifications->push([
                'link' => route('reporter.tickets', ['user' => $user->display_name]),
                'title' => $ticketFeedback . ' ' . __res('ticket', $ticketFeedback) . ' awaiting your feedback',
            ]);
        }

        $unreadCount = $notifications->filter(fn ($notification) => !($notification['read'] ?? false))->count();

        return view('components.notifications.ticket')
            ->with('notifications', $notifications)
            ->with('count', $unreadCount);
    }
}
