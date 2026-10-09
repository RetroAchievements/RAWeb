<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserPermissionsData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TicketShowPageProps')]
class TicketShowPagePropsData extends Data
{
    public function __construct(
        public TicketListEntryData $ticket,
        public ?AchievementData $achievement,
        public ?LeaderboardData $leaderboard,
        public string $ticketableDescription,
        public bool $hasMaintainer,
        public UserPermissionsData $can,
        /** @var TicketRelatedEntryData[] $relatedTickets */
        public array $relatedTickets,
        public ?TicketReporterUnlockData $reporterUnlock,
        public ?int $unlocksSinceReported,
        public ?int $reportedTriggerVersion,
        public ?int $currentTriggerVersion,
        public ?LeaderboardEntryData $reporterLeaderboardEntry,
        public ?int $leaderboardEntryCount,
    ) {
    }
}
