<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('ReportAchievementIssuePageProps')]
class ReportAchievementIssuePagePropsData extends Data
{
    public function __construct(
        public AchievementData $achievement,
        public bool $hasSession,
        public int $ticketType,
        public ?string $extra,
    ) {
    }
}
