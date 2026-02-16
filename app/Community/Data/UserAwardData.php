<?php

namespace App\Community\Data;

use App\Community\Enums\AwardType;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('UserAwardData')]
class UserAwardData extends Data
{
    public function __construct(
        public string $imageUrl,
        public string $tooltip,
        public ?string $link,
        public bool $isGold,
        public ?int $gameId,
        public string $dateAwarded,
        public AwardType $awardType,
        public string $awardSection,
        public int $displayOrder,
    ) {}
}
