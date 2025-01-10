<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use App\Enums\PlayerGameActivityEventType;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('PlayerGameActivityEvent')]
class PlayerGameActivityEventData extends Data
{
    public function __construct(
        public PlayerGameActivityEventType $type,
        /** Rich presence, etc. */
        public ?string $description = null,
        public ?string $header = null,
        public ?Carbon $when = null,
        public ?int $id = null,
        public ?bool $hardcore = null,
        public ?AchievementData $achievement = null,
        public ?UserData $unlocker = null,
        public ?bool $hardcoreLater = null,
    ) {
    }
}
