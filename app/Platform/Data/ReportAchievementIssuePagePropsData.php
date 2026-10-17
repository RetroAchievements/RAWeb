<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Enums\TicketType;
use App\Data\UserPermissionsData;
use App\Platform\Enums\TicketCreationBlockReason;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('ReportAchievementIssuePageProps')]
class ReportAchievementIssuePagePropsData extends Data
{
    public function __construct(
        public AchievementData $achievement,
        public bool $hasSession,
        public TicketType $ticketType,
        public ?string $extra,
        public UserPermissionsData $can,
        public ?TicketCreationBlockReason $ticketBlockReason = null,
        public bool $hasCasualUnlockFromRestrictedClient = false,
    ) {
    }
}
