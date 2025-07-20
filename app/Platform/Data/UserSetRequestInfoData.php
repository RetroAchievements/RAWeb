<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('UserSetRequestInfo')]
class UserSetRequestInfoData extends Data
{
    public function __construct(
        public int $total,
        public int $used,
        public int $remaining,
        public int $pointsForNext,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            total: (int) $data['total'],
            used: (int) $data['used'],
            remaining: (int) $data['remaining'],
            pointsForNext: (int) $data['pointsForNext'],
        );
    }
}
