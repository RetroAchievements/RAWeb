<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Data\CommentData;
use App\Community\Enums\TicketAction;
use App\Data\UserPermissionsData;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\AutoInertiaDeferred;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
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
        public bool $isViewerReporter,
        public UserPermissionsData $can,
        /** @var TicketRelatedEntryData[] $relatedTickets */
        public array $relatedTickets,
        public ?TicketReporterUnlockData $reporterUnlock,
        public ?int $unlocksSinceReported,
        public ?int $reportedTriggerVersion,
        public ?int $currentTriggerVersion,
        public ?LeaderboardEntryData $reporterLeaderboardEntry,
        public ?int $leaderboardEntryCount,
        public ?string $reportBody,
        /** @var CommentData[] $comments */
        public array $comments,
        /** @var TicketChangelogEntryData[] $changelog */
        public array $changelog,
        /** @var TicketAction[] $allowedTicketActions */
        public array $allowedTicketActions,
        public bool $hasNonReporterComment,
        public bool $hasReporterRepliedLast,
        public bool $hasReporterHistoryAccess,
        public ?Carbon $autoCloseAt,
        #[AutoInertiaDeferred]
        public Lazy|PlayerGameActivityData|null $reporterActivity,
    ) {
    }
}
