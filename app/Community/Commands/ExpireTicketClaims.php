<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Community\Enums\TicketState;
use App\Enums\UserPreference;
use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Ticket\TicketClaimExpiryWarningNotification;
use App\Platform\Actions\GetTicketClaimStatusAction;
use App\Platform\Actions\ReleaseTicketClaimAction;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class ExpireTicketClaims extends Command
{
    protected $signature = 'ra:community:ticket:expire-claims';
    protected $description = 'Warns developers when their ticket claims are about to expire. Expired claims are reassigned back to the triggerable maintainer.';

    public function __construct(
        private readonly GetTicketClaimStatusAction $getTicketClaimStatusAction,
        private readonly ReleaseTicketClaimAction $releaseTicketClaimAction,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $serverAccount = User::whereName('Server')->firstOrFail();

        $claimedTickets = Ticket::query()
            ->select(['id', 'state', 'ticketable_type', 'ticketable_id', 'assignee_id', 'reporter_id', 'created_at', 'updated_at'])
            ->where('state', TicketState::Open)
            ->whereNotNull('assignee_id')
            ->withLiveTicketable()
            ->with([
                'assignee.roles',
                'ticketable' => fn (MorphTo $ticketable) => $ticketable->morphWith([
                    Achievement::class => ['activeMaintainer', 'developer'],
                    Leaderboard::class => ['developer'],
                ]),
            ])
            ->get()
            ->filter(fn (Ticket $ticketRecord): bool => $ticketRecord->isClaimed());

        foreach ($claimedTickets as $claimedTicket) {
            // If someone lost their dev role, immediately release their ticket claims.
            if (!$claimedTicket->assignee?->hasAnyRole([Role::DEVELOPER, Role::DEVELOPER_JUNIOR])) {
                $this->releaseTicketClaimAction->execute($claimedTicket, $serverAccount, isExpired: true);

                continue;
            }

            $claimDeadlines = $this->getTicketClaimStatusAction->execute($claimedTicket);
            if (!$claimDeadlines->warnAt || !$claimDeadlines->expiresAt) {
                continue;
            }

            if (!$claimDeadlines->isWarned) {
                if ($claimDeadlines->warnAt->isPast()) {
                    $this->sendExpiryWarning($claimedTicket, $serverAccount, $claimDeadlines->expiresAt);
                }

                continue;
            }

            if ($claimDeadlines->expiresAt->isFuture()) {
                continue;
            }

            $this->releaseTicketClaimAction->execute($claimedTicket, $serverAccount, isExpired: true);
        }
    }

    private function sendExpiryWarning(Ticket $claimedTicket, User $serverAccount, Carbon $expiresAt): void
    {
        activity()
            ->causedBy($serverAccount)
            ->performedOn($claimedTicket)
            ->event(Ticket::CLAIM_EXPIRY_WARNED_EVENT)
            ->log(Ticket::CLAIM_EXPIRY_WARNED_EVENT);

        if (BitSet($claimedTicket->assignee->preferences_bitfield, UserPreference::EmailOn_TicketActivity)) {
            $claimedTicket->assignee->notify(new TicketClaimExpiryWarningNotification($claimedTicket, $expiresAt));
        }
    }
}
