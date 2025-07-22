<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use App\Models\GameRecentPlayer;
use App\Models\PlayerBadge;
use App\Models\PlayerGame;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameRecentPlayer')]
class GameRecentPlayerData extends Data
{
    #[Computed]
    public bool $isActive;

    public function __construct(
        public UserData $user,
        public string $richPresence,
        public Carbon $richPresenceUpdatedAt,
        public int $achievementsUnlocked,
        public int $achievementsUnlockedSoftcore,
        public int $achievementsUnlockedHardcore,
        public int $points,
        public int $pointsHardcore,
        public Lazy|PlayerBadgeData|null $highestAward,
    ) {
        $this->isActive = $this->richPresenceUpdatedAt->diffInMinutes(now()) <= 5;
    }

    public static function fromGameRecentPlayer(
        GameRecentPlayer $gameRecentPlayer,
        ?PlayerGame $playerGame = null,
        ?PlayerBadge $highestAward = null
    ): self {
        $achievementsUnlocked = $playerGame?->achievements_unlocked ?? 0;
        $achievementsUnlockedHardcore = $playerGame?->achievements_unlocked_hardcore ?? 0;
        $achievementsUnlockedSoftcore = $achievementsUnlocked - $achievementsUnlockedHardcore;

        $points = $playerGame?->points ?? 0;
        $pointsHardcore = $playerGame?->points_hardcore ?? 0;

        return new self(
            user: UserData::fromUser($gameRecentPlayer->user)->include('displayName'),
            richPresence: $gameRecentPlayer->rich_presence ?? '',
            richPresenceUpdatedAt: $gameRecentPlayer->rich_presence_updated_at,
            achievementsUnlocked: $achievementsUnlocked,
            achievementsUnlockedSoftcore: $achievementsUnlockedSoftcore,
            achievementsUnlockedHardcore: $achievementsUnlockedHardcore,
            points: $points,
            pointsHardcore: $pointsHardcore,
            highestAward: $highestAward
                ? Lazy::create(fn () => PlayerBadgeData::fromPlayerBadge($highestAward)->include('awardType', 'awardDate', 'awardDataExtra'))
                : null,
        );
    }
}
