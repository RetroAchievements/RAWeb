<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\PaginatedData;
use App\Data\UserPermissionsData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('MessageThreadShowPageProps<TItems = App.Community.Data.Message>')]
class MessageThreadShowPagePropsData extends Data
{
    public function __construct(
        public MessageThreadData $messageThread,
        public PaginatedData $paginatedMessages,
        public ShortcodeDynamicEntitiesData $dynamicEntities,
        public UserPermissionsData $can,
        public bool $canReply,
        public ?string $senderUserAvatarUrl,
        public string $senderUserDisplayName,
    ) {
    }
}
