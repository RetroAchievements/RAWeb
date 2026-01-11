<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\PaginatedData;
use App\Platform\Data\EventData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('EventCommentsPageProps<TItems = App.Community.Data.Comment>')]
class EventCommentsPagePropsData extends Data
{
    public function __construct(
        public EventData $event,
        public PaginatedData $paginatedComments,
        public bool $isSubscribed,
        public bool $canComment,
    ) {
    }
}
