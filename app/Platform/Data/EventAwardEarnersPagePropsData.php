<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\PaginatedData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('EventAwardEarnersPageProps<TItems = App.Platform.Data.AwardEarner>')]
class EventAwardEarnersPagePropsData extends Data
{
    public function __construct(
        public EventData $event,
        public EventAwardData $eventAward,
        public PaginatedData $paginatedUsers,
    ) {
    }
}
