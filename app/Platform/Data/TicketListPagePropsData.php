<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\PaginatedData;
use App\Data\UserData;
use App\Platform\Enums\TicketListScope;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TicketListPageProps')]
class TicketListPagePropsData extends Data
{
    /**
     * @param TicketListFilterData[] $availableFilters
     */
    public function __construct(
        public TicketListScope $scope,
        #[LiteralTypeScriptType('App.Data.PaginatedData<App.Platform.Data.TicketListEntry>')]
        public PaginatedData $paginatedTickets,
        public TicketListStateCountsData $stateCounts,
        #[LiteralTypeScriptType('App.Platform.Data.TicketListFilter[]')]
        public array $availableFilters,
        public ?GameData $game = null,
        public ?AchievementData $achievement = null,
        public ?UserData $user = null,
    ) {
    }
}
