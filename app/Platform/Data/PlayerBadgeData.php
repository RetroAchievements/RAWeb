<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Enums\AwardType;
use App\Models\PlayerBadge;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('PlayerBadge')]
class PlayerBadgeData extends Data
{
    public function __construct(
        public AwardType $awardType,
        public int $awardKey,
        public int $awardTier,
        public Carbon $awardDate,
    ) {
    }

    public static function fromPlayerBadge(PlayerBadge $playerBadge): self
    {
        return new self(
            awardType: $playerBadge->award_type,
            awardKey: $playerBadge->award_key,
            awardTier: $playerBadge->award_tier,
            awardDate: $playerBadge->awarded_at,
        );
    }
}
