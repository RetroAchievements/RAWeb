<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TicketInboxPageProps')]
class TicketInboxPagePropsData extends Data
{
    /**
     * @param TicketInboxSectionData[] $sections
     */
    public function __construct(
        public UserData $user,
        public array $sections,
        public int $sectionLimit,
        public int $attentionCount,
    ) {
    }
}
