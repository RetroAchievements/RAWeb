<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\CommentableType;
use App\Community\Enums\TicketAction;
use App\Community\Enums\TicketState;
use App\Enums\UserPreference;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Ticket\TicketStatusUpdatedNotification;
use App\Platform\Services\UserTicketCountService;

class ChangeTicketStateAction
{
    public function __construct(
        private readonly UserTicketCountService $userTicketCountService,
    ) {
    }

    public function execute(Ticket $ticket, TicketAction $action, User $actor): void
    {
        $previousState = $ticket->state;
        $newState = $action->targetState();
        $ticket->state = $newState;

        if ($newState->isResolved()) {
            $ticket->resolved_at = now();
            $ticket->resolver_id = $actor->id;
            $ticket->resolution = $action->resolution();
        } elseif ($previousState->isResolved()) {
            // Clear any resolver info when reopening a previously resolved ticket.
            $ticket->resolved_at = null;
            $ticket->resolver_id = null;
            $ticket->resolution = null;
        }

        $ticket->save();

        if ($action === TicketAction::Demoted && $ticket->ticketable) {
            $ticket->getTicketableModel()->demoteForTicket($actor);
        }

        $comment = $this->buildServerComment($action, $previousState, $actor);

        // add the system comment without generating an email. subscribers get an email below.
        $serverUser = User::whereName('Server')->first();
        if ($serverUser) {
            Comment::create([
                'commentable_type' => CommentableType::AchievementTicket,
                'commentable_id' => $ticket->id,
                'body' => $comment,
                'user_id' => $serverUser->id,
            ]);
        }

        if ($ticket->ticketable_author_id) {
            $this->userTicketCountService->clearForUserId($ticket->ticketable_author_id);
        }

        if ($ticket->reporter) {
            $this->userTicketCountService->clearForUserId($ticket->reporter->id);

            // Only send email if the reporter has email notifications enabled for ticket activity.
            if (BitSet($ticket->reporter->preferences_bitfield, UserPreference::EmailOn_TicketActivity)) {
                $ticket->reporter->notify(
                    new TicketStatusUpdatedNotification($ticket, $actor, $newState->label(), $comment)
                );
            }
        }
    }

    private function buildServerComment(TicketAction $action, TicketState $previousState, User $actor): string
    {
        $actorName = $actor->display_name;

        return match ($action) {
            TicketAction::Resolved => "Ticket resolved as fixed by {$actorName}.",
            TicketAction::Request => "Ticket reassigned to reporter by {$actorName}.",
            TicketAction::Reopen => match ($previousState) {
                TicketState::Request => "Ticket reassigned to author by {$actorName}.",
                TicketState::Quarantined => "Ticket approved by {$actorName}.",
                default => "Ticket reopened by {$actorName}.",
            },
            TicketAction::ClosedMistaken,
            TicketAction::Demoted,
            TicketAction::NotEnoughInfo,
            TicketAction::WrongRom,
            TicketAction::Network,
            TicketAction::UnableToReproduce,
            TicketAction::UnableToDebug,
            TicketAction::ClosedOther => $action->resolution()->closeCommentBody($actorName),
        };
    }
}
