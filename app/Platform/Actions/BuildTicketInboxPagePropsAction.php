<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\TicketState;
use App\Data\UserData;
use App\Data\UserPermissionsData;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Actions\Concerns\LoadsTicketListEntryRelations;
use App\Platform\Data\TicketInboxPagePropsData;
use App\Platform\Data\TicketInboxSectionData;
use App\Platform\Enums\TicketInboxSectionKind;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BuildTicketInboxPagePropsAction
{
    use LoadsTicketListEntryRelations;

    private const SECTION_LIMIT = 8;

    /**
     * @param User $target the user whose inbox is shown, which differs from the viewer when `?user=` is set
     * @param User|null $viewer the user actually looking, which decides whether rows are allowed to carry ticket signals
     */
    public function execute(User $target, ?User $viewer): TicketInboxPagePropsData
    {
        /** @var Collection<int, Ticket> $uniqueTickets */
        $uniqueTickets = new Collection();
        $sectionCounts = [];
        $sectionTickets = [];
        foreach (TicketInboxSectionKind::cases() as $kind) {
            $sectionCounts[$kind->value] = $this->sectionQuery($kind, $target)->count();
            $sectionTickets[$kind->value] = $sectionCounts[$kind->value] === 0
                ? new Collection()
                : $this->fetchTicketsForSection($kind, $target);

            $uniqueTickets = $uniqueTickets->merge($sectionTickets[$kind->value]);
        }

        $signalsForTickets = $this->resolveTicketSignalsForViewer($uniqueTickets, $viewer);

        $sections = array_map(
            fn (TicketInboxSectionKind $kind) => new TicketInboxSectionData(
                kind: $kind,
                count: $sectionCounts[$kind->value],
                tickets: $this->resolveTicketListEntries($sectionTickets[$kind->value], $signalsForTickets),
            ),
            TicketInboxSectionKind::cases(),
        );

        $attentionCount = array_sum(array_map(
            fn (TicketInboxSectionData $section) => $section->kind->needsViewerAction() ? $section->count : 0,
            $sections,
        ));

        return new TicketInboxPagePropsData(
            attentionCount: $attentionCount,
            can: UserPermissionsData::fromUser($viewer)->include('viewTriageSignals'),
            sectionLimit: self::SECTION_LIMIT,
            sections: $sections,
            user: UserData::fromUser($target),
        );
    }

    /**
     * @return Collection<int, Ticket>
     */
    private function fetchTicketsForSection(TicketInboxSectionKind $kind, User $target): Collection
    {
        $query = $this->sectionQuery($kind, $target)
            ->orderByDesc($kind->sortColumn())
            ->orderByDesc('id')
            ->limit(self::SECTION_LIMIT);

        $this->applyTicketListEntryEagerLoads($query);

        return $query->get();
    }

    /**
     * @return Builder<Ticket>
     */
    private function sectionQuery(TicketInboxSectionKind $kind, User $target): Builder
    {
        $query = Ticket::query()->withLiveTicketable();

        return match ($kind) {
            TicketInboxSectionKind::ToResolve => $query
                ->forAssignee($target)
                ->where('state', TicketState::Open),

            TicketInboxSectionKind::AwaitingYourFeedback => $query
                ->where('reporter_id', $target->id)
                ->where('state', TicketState::Request),

            TicketInboxSectionKind::AwaitingReporter => $query
                ->forAssignee($target)
                ->where('state', TicketState::Request),

            TicketInboxSectionKind::ReportedByYou => $query
                ->where('reporter_id', $target->id),

            TicketInboxSectionKind::ResolvedByYou => $query
                ->where('resolver_id', $target->id)
                ->whereIn('state', [TicketState::Resolved, TicketState::Closed]),
        };
    }
}
