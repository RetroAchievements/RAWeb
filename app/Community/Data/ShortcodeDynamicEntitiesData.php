<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\UserData;
use App\Platform\Data\AchievementData;
use App\Platform\Data\GameData;
use App\Platform\Data\GameSetData;
use App\Platform\Data\TicketData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('ShortcodeDynamicEntities')]
class ShortcodeDynamicEntitiesData extends Data
{
    /**
     * @param UserData[] $users
     * @param TicketData[] $tickets
     * @param AchievementData[] $achievements
     * @param GameData[] $games
     * @param GameSetData[] $hubs
     */
    public function __construct(
        public array $users = [],
        public array $tickets = [],
        public array $achievements = [],
        public array $games = [],
        public array $hubs = [],
    ) {
    }
}
