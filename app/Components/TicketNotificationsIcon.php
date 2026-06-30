<?php

declare(strict_types=1);

namespace App\Components;

use App\Community\Enums\TicketState;
use App\Models\User;
use App\Platform\Services\UserTicketCountService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TicketNotificationsIcon extends Component
{
    public ?string $class = null;

    public function render(): View
    {
        /** @var User $user */
        $user = request()->user();

        $userTicketCountService = app(UserTicketCountService::class);

        $notifications = collect();

        // Open ticket notifications
        $openTicketsData = $userTicketCountService->countOpenForDev($user);
        if ($openTicketsData[TicketState::Open->value]) {
            $notifications->push([
                'link' => route('developer.tickets', ['user' => $user->display_name]),
                'title' => $openTicketsData[TicketState::Open->value] . ' ' . __res('ticket', (int) $openTicketsData[TicketState::Open->value]) . ' for you to resolve',
                'class' => 'text-danger',
            ]);
        }
        if ($openTicketsData[TicketState::Request->value]) {
            $notifications->push([
                'link' => route('developer.tickets', ['user' => $user->display_name]),
                'title' => $openTicketsData[TicketState::Request->value] . ' ' . __res('ticket', (int) $openTicketsData[TicketState::Request->value]) . ' pending feedback',
                'read' => true,
            ]);
        }

        $ticketFeedback = $userTicketCountService->countRequestsForReporter($user);
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
