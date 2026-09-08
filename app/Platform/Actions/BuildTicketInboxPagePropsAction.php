<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\TicketState;
use App\Data\UserData;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Actions\Concerns\LoadsTicketListEntryRelations;
use App\Platform\Data\TicketInboxPagePropsData;
use App\Platform\Data\TicketInboxSectionData;
use App\Platform\Enums\TicketInboxSectionKind;
use Illuminate\Database\Eloquent\Builder;

class BuildTicketInboxPagePropsAction
{
    use LoadsTicketListEntryRelations;

    private const SECTION_LIMIT = 8;

    public function execute(User $viewer): TicketInboxPagePropsData
    {
        $sections = array_map(
            fn (TicketInboxSectionKind $kind) => $this->buildSection($kind, $viewer),
            TicketInboxSectionKind::cases(),
        );

        $attentionCount = array_sum(array_map(
            fn (TicketInboxSectionData $section) => $section->kind->needsViewerAction() ? $section->count : 0,
            $sections,
        ));

        return new TicketInboxPagePropsData(
            user: UserData::fromUser($viewer),
            sections: $sections,
            sectionLimit: self::SECTION_LIMIT,
            attentionCount: $attentionCount,
        );
    }

    private function buildSection(TicketInboxSectionKind $kind, User $viewer): TicketInboxSectionData
    {
        $count = $this->sectionQuery($kind, $viewer)->count();

        $query = $this->sectionQuery($kind, $viewer)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::SECTION_LIMIT);

        $this->applyTicketListEntryEagerLoads($query);

        return new TicketInboxSectionData(
            kind: $kind,
            count: $count,
            tickets: $count ? $this->fetchTicketListEntries($query) : [],
        );
    }

    /**
     * @return Builder<Ticket>
     */
    private function sectionQuery(TicketInboxSectionKind $kind, User $viewer): Builder
    {
        $query = Ticket::query()->withLiveTicketable();

        return match ($kind) {
            TicketInboxSectionKind::ToResolve => $query
                ->forAssignee($viewer)
                ->where('state', TicketState::Open),

            TicketInboxSectionKind::AwaitingYourFeedback => $query
                ->where('reporter_id', $viewer->id)
                ->where('state', TicketState::Request),

            TicketInboxSectionKind::AwaitingReporter => $query
                ->forAssignee($viewer)
                ->where('state', TicketState::Request),

            TicketInboxSectionKind::ReportedOpen => $query
                ->where('reporter_id', $viewer->id)
                ->where('state', TicketState::Open),

            TicketInboxSectionKind::ResolvedByYou => $query
                ->where('resolver_id', $viewer->id)
                ->where('state', TicketState::Resolved),
        };
    }
}
