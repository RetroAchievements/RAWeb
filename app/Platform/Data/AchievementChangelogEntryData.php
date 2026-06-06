<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use App\Platform\Enums\AchievementChangelogEntryType;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AchievementChangelogEntry')]
class AchievementChangelogEntryData extends Data
{
    /**
     * @param ChangelogFieldChangeData[] $fieldChanges
     */
    public function __construct(
        public AchievementChangelogEntryType $type,
        public Carbon $createdAt,
        public ?UserData $user = null,
        public array $fieldChanges = [],
        public int $count = 1,
    ) {
    }
}
