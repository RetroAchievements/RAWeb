<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\PlayerBadge;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('PlayerBadge')]
class PlayerBadgeData extends Data
{
    public function __construct(
        public int $awardType,
        public int $awardData,
        public int $awardDataExtra,
        public Carbon $awardDate,
    ) {
    }

    public static function fromPlayerBadge(PlayerBadge $playerBadge): self
    {
        return new self(
            awardType: $playerBadge->AwardType,
            awardData: $playerBadge->AwardData,
            awardDataExtra: $playerBadge->AwardDataExtra,
            awardDate: $playerBadge->AwardDate,
        );
    }
}
