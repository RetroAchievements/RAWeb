<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use App\Community\Enums\TicketState;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Requests\TicketListRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Drives filtering the ticket list/page by a specific criteria.
 */
#[TypeScript]
enum TicketListScope: string
{
    case All = 'all';
    case Game = 'game';
    case Achievement = 'achievement';
    case AssignedTo = 'assignedTo';
    case ReportedBy = 'reportedBy';
    case AwaitingReporter = 'awaitingReporter';
    case ResolvedBy = 'resolvedBy';

    public function resolveTarget(TicketListRequest $request): Game|Achievement|User|null
    {
        return match ($this) {
            self::All => null,
            self::Game => Game::findOrFail((int) $request->input('game')),
            self::Achievement => Achievement::findOrFail((int) $request->input('achievement')),
            self::AssignedTo => User::withTrashed()->findOrFail((int) $request->input('user')),
            self::ReportedBy, self::AwaitingReporter, self::ResolvedBy => User::findOrFail((int) $request->input('user')),
        };
    }

    /**
     * @return Builder<Ticket>
     */
    public function baseQuery(Game|Achievement|User|null $target): Builder
    {
        return match ($this) {
            self::All => Ticket::query(),
            self::Game => Ticket::forGame($target),
            self::Achievement => Ticket::forAchievement($target),
            self::AssignedTo => Ticket::forAssignee($target),
            self::ReportedBy => Ticket::query()
                ->where('reporter_id', $target->id),
            self::AwaitingReporter => Ticket::query()
                ->where('reporter_id', $target->id),
            self::ResolvedBy => Ticket::query()
                ->where('resolver_id', $target->id)
                ->whereIn('state', [TicketState::Resolved, TicketState::Closed]),
        };
    }

    /**
     * The filters usable by a given scope.
     *
     * @return TicketListFilterKind[]
     */
    public function filterKinds(): array
    {
        return match ($this) {
            self::All, self::Game => [
                TicketListFilterKind::Type,
                TicketListFilterKind::PublishedStatus,
                TicketListFilterKind::Mode,
                TicketListFilterKind::DeveloperType,
                TicketListFilterKind::Emulator,
            ],
            self::Achievement => [
                TicketListFilterKind::Type,
                TicketListFilterKind::Mode,
                TicketListFilterKind::Emulator,
            ],
            self::AssignedTo, self::ReportedBy => [
                TicketListFilterKind::Type,
                TicketListFilterKind::PublishedStatus,
                TicketListFilterKind::Mode,
                TicketListFilterKind::Emulator,
            ],
            self::AwaitingReporter => [],
            self::ResolvedBy => [
                TicketListFilterKind::Type,
                TicketListFilterKind::PublishedStatus,
                TicketListFilterKind::Mode,
                TicketListFilterKind::Developer,
                TicketListFilterKind::Reporter,
                TicketListFilterKind::Emulator,
            ],
        };
    }

    public function defaultStatusFilter(): TicketListStatusFilter
    {
        return match ($this) {
            self::ResolvedBy => TicketListStatusFilter::All,

            self::AwaitingReporter => TicketListStatusFilter::Request,
            default => TicketListStatusFilter::Unresolved,
        };
    }

    public function comparisonUser(?Model $target): ?User
    {
        return match ($this) {
            self::ResolvedBy => $target instanceof User ? $target : null,
            default => null,
        };
    }
}
