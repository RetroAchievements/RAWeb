<?php

declare(strict_types=1);

namespace App\Components;

use App\Community\Enums\TicketFilters;
use App\Models\AchievementSetClaim;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class GeneralNotificationsIcon extends Component
{
    public ?string $class = null;

    public function render(): View
    {
        /** @var User $user */
        $user = request()->user();

        $notifications = collect();

        $dot = null;

        if ($user->unread_messages_count) {
            $notifications->push([
                'link' => route('message-thread.index'),
                'title' => $user->unread_messages_count . ' ' . __res('message', (int) $user->unread_messages_count),
                'priority' => 0,
            ]);
        }

        // Ticket feedback for users without manage permissions
        if (!$user->can('manage', Ticket::class)) {
            $ticketFeedback = countRequestTicketsByUser($user);
            if ($ticketFeedback) {
                $notifications->push([
                    'link' => url('/ticketmanager.php?p=' . $user->User . '&t=' . (TicketFilters::Default & ~TicketFilters::StateOpen)),
                    'title' => $ticketFeedback . ' ' . __res('ticket', $ticketFeedback) . ' awaiting your feedback',
                    'priority' => 1,
                ]);
            }
        }

        // Claim expiry notifications
        if ($user->can('manage', AchievementSetClaim::class)) {
            $expiringClaims = getExpiringClaim($user->User);
            if ($expiringClaims['Expired'] ?? 0) {
                $notifications->push([
                    'link' => url('/expiringclaims.php?u=' . $user->User),
                    'title' => 'Claim Expired',
                    'class' => 'text-danger',
                    'priority' => 2,
                ]);
            } elseif ($expiringClaims['Expiring'] ?? 0) {
                $notifications->push([
                    'link' => url('/expiringclaims.php?u=' . $user->User),
                    'title' => 'Claim Expiring Soon',
                    'class' => 'text-danger',
                    'priority' => 1,
                ]);
            }
        }

        $unreadCount = $notifications->filter(fn ($notification) => !($notification['read'] ?? false))->count();
        $priority = $notifications->max('priority');

        return view('components.notifications.general')
            ->with('notifications', $notifications)
            ->with('count', $unreadCount)
            ->with('priority', $priority);
    }
}
