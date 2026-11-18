<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GamePageClaimData')]
class GamePageClaimData extends Data
{
    public function __construct(
        public int $numUnresolvedTickets,
        public ?AchievementSetClaimData $userClaim,
        public bool $isSoleAuthor,
        public bool $wouldBeCollaboration,
        public bool $wouldBeRevision,
    ) {
    }
}
