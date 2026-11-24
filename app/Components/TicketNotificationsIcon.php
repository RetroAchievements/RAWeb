<?php

declare(strict_types=1);

namespace App\Components;

use App\Community\Enums\TicketState;
use App\Models\User;
use App\Platform\Services\UserTicketCountService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class TicketNotificationsIcon extends Component
{
    public function __construct(
        public string $class = '',
    ) {
    }

    public function render(): View
    {
        /** @var User $user */
        $user = request()->user();

        $notifications = self::buildNotifications($user);

        $unreadCount = $notifications->filter(fn ($notification) => !($notification['read'] ?? false))->count();

        return view('components.notifications.ticket')
            ->with('notifications', $notifications)
            ->with('count', $unreadCount);
    }

    /**
     * @return Collection<int, array{link: string, title: string, class?: string, read?: bool}>
     */
    private static function buildNotifications(User $user): Collection
    {
        return once(function () use ($user) {
            $userTicketCountService = app(UserTicketCountService::class);

            $notifications = collect();

            // Open ticket notifications
            $openTicketsData = $userTicketCountService->countOpenForAssignee($user);
            if ($openTicketsData[TicketState::Open->value]) {
                $notifications->push([
                    'link' => route('tickets.mine'),
                    'title' => $openTicketsData[TicketState::Open->value] . ' ' . __res('ticket', (int) $openTicketsData[TicketState::Open->value]) . ' for you to resolve',
                    'class' => 'text-danger',
                ]);
            }
            if ($openTicketsData[TicketState::Request->value]) {
                $notifications->push([
                    'link' => route('tickets.mine'),
                    'title' => $openTicketsData[TicketState::Request->value] . ' ' . __res('ticket', (int) $openTicketsData[TicketState::Request->value]) . ' pending feedback',
                    'read' => true,
                ]);
            }

            $ticketFeedback = $userTicketCountService->countRequestsForReporter($user);
            if ($ticketFeedback) {
                $notifications->push([
                    'link' => route('tickets.mine'),
                    'title' => $ticketFeedback . ' ' . __res('ticket', $ticketFeedback) . ' awaiting your feedback',
                ]);
            }

            return $notifications;
        });
    }
}
