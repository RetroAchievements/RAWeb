<?php

declare(strict_types=1);

namespace App\Components;

use App\Models\AchievementSetClaim;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Services\UserTicketCountService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

/**
 * The navbar envelope. Its dropdown lists unread messages, plus ticket
 * feedback and claim expiry alerts.
 */
class MessageNotificationsIcon extends Component
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

        return view('components.notifications.message')
            ->with('notifications', $notifications)
            ->with('count', $notifications->count());
    }

    /**
     * The navbar renders this icon twice, once for desktop and once for mobile,
     * so the list is built once per request and shared. It is static so once()
     * keys on the user, not on the component instance.
     *
     * @return Collection<int, array{link: string, title: string, class?: string}>
     */
    private static function buildNotifications(User $user): Collection
    {
        return once(function () use ($user) {
            $notifications = collect();

            if ($user->unread_messages) {
                $notifications->push([
                    'link' => route('message-thread.index'),
                    'title' => $user->unread_messages . ' ' . __res('message', (int) $user->unread_messages),
                ]);
            }

            // Ticket feedback for users without manage permissions.
            if (!$user->can('manage', Ticket::class)) {
                $ticketFeedback = app(UserTicketCountService::class)->countRequestsForReporter($user);
                if ($ticketFeedback) {
                    $notifications->push([
                        'link' => route('tickets.mine'),
                        'title' => $ticketFeedback . ' ' . __res('ticket', $ticketFeedback) . ' awaiting your feedback',
                    ]);
                }
            }

            // Claim expiry notifications.
            if ($user->can('create', AchievementSetClaim::class)) {
                $expiringClaims = getExpiringClaim($user);
                if ($expiringClaims['Expired'] ?? 0) {
                    $notifications->push([
                        'link' => route('developer.claims', ['user' => $user->display_name]),
                        'title' => 'Claim Expired',
                        'class' => 'text-danger',
                    ]);
                } elseif ($expiringClaims['Expiring'] ?? 0) {
                    $notifications->push([
                        'link' => route('developer.claims', ['user' => $user->display_name]),
                        'title' => 'Claim Expiring Soon',
                        'class' => 'text-danger',
                    ]);
                }
            }

            return $notifications;
        });
    }
}
