<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\PaginatedData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('MessageThreadIndexPageProps<TItems = App.Community.Data.MessageThread>')]
class MessageThreadIndexPagePropsData extends Data
{
    public function __construct(
        public PaginatedData $paginatedMessageThreads,
        public int $unreadMessageCount,
        public string $senderUserDisplayName,
        /** @var string[] */
        public array $selectableInboxDisplayNames,
    ) {
    }
}
