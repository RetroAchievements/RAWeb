<?php

declare(strict_types=1);

namespace App\Site\Components;

use App\Community\Enums\TicketFilters;
use App\Community\Enums\TicketState;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class NotificationIcon extends Component
{
    public ?string $class = null;

    public function render(): View
    {
        /** @var User $user */
        $user = request()->user();

        $notifications = collect();

        if ($user->unread_messages_count) {
            $notifications->push([
                'link' => url('inbox.php'),
                'title' => $user->unread_messages_count . ' ' . __res('message', (int) $user->unread_messages_count),
            ]);
        }

        // Ticket notifications
        if ($user->getAttribute('Permissions') >= Permissions::JuniorDeveloper) {
            $openTicketsData = countOpenTicketsByDev($user->User);
            if ($openTicketsData[TicketState::Open]) {
                $notifications->push([
                    'link' => url('/ticketmanager.php?u=' . $user->User . '&t=' . (TicketFilters::Default & ~TicketFilters::StateRequest)),
                    'title' => $openTicketsData[TicketState::Open] . ' ' . __res('ticket', (int) $openTicketsData[TicketState::Open]) . ' for you to resolve',
                    'class' => 'text-danger',
                ]);
            }
            if ($openTicketsData[TicketState::Request]) {
                $notifications->push([
                    'link' => url('/ticketmanager.php?u=' . $user->User . '&t=' . (TicketFilters::Default & ~TicketFilters::StateOpen)),
                    'title' => $openTicketsData[TicketState::Request] . ' ' . __res('ticket', (int) $openTicketsData[TicketState::Request]) . ' pending feedback',
                    'read' => true,
                ]);
            }
        }
        $ticketFeedback = countRequestTicketsByUser($user);
        if ($ticketFeedback) {
            $notifications->push([
                'link' => url('/ticketmanager.php?p=' . $user->User . '&t=' . (TicketFilters::Default & ~TicketFilters::StateOpen)),
                'title' => $ticketFeedback . ' ' . __res('ticket', $ticketFeedback) . ' awaiting your feedback',
            ]);
        }

        // Claim expiry notifications
        if ($user->getAttribute('Permissions') >= Permissions::JuniorDeveloper) {
            $expiringClaims = getExpiringClaim($user->User);
            if ($expiringClaims['Expired'] ?? 0) {
                $notifications->push([
                    'link' => url('/expiringclaims.php?u=' . $user->User),
                    'title' => 'Claim Expired',
                    'class' => 'text-danger',
                ]);
            } elseif ($expiringClaims['Expiring'] ?? 0) {
                $notifications->push([
                    'link' => url('/expiringclaims.php?u=' . $user->User),
                    'title' => 'Claim Expiring Soon',
                    'class' => 'text-danger',
                ]);
            }
        }

        $unreadCount = $notifications->filter(fn ($notification) => !($notification['read'] ?? false))->count();

        return view('components.notification.icon')
            ->with('notifications', $notifications)
            ->with('count', $unreadCount);
    }
}
