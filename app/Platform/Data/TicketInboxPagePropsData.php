<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use App\Data\UserPermissionsData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TicketInboxPageProps')]
class TicketInboxPagePropsData extends Data
{
    /**
     * @param TicketInboxSectionData[] $sections
     */
    public function __construct(
        public array $sections,
        public int $attentionCount,
        public int $sectionLimit,
        public UserData $user,
        public UserPermissionsData $can,
    ) {
    }
}
