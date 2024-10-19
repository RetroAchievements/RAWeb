<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Enums\AwardType;
use App\Models\PlayerGame;
use App\Platform\Enums\UnlockMode;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('PlayerGame')]
class PlayerGameData extends Data
{
    public function __construct(
        // Model fields
        public ?int $achievementsUnlocked,
        public ?int $achievementsUnlockedHardcore,
        public ?int $achievementsUnlockedSoftcore,
        public ?Carbon $beatenAt,
        public ?Carbon $beatenHardcoreAt,
        public ?Carbon $completedAt,
        public ?Carbon $completedHardcoreAt,
        public ?int $points,
        public ?int $pointsHardcore,

        // Derived fields
        public Lazy|PlayerBadgeData|null $highestAward,
    ) {
    }

    public static function fromPlayerGame(PlayerGame $playerGame): self
    {
        return new self(
            achievementsUnlocked: $playerGame->achievements_unlocked,
            achievementsUnlockedHardcore: $playerGame->achievements_unlocked_hardcore,
            achievementsUnlockedSoftcore: $playerGame->achievements_unlocked_softcore,
            beatenAt: $playerGame->beaten_at,
            beatenHardcoreAt: $playerGame->beaten_hardcore_at,
            completedAt: $playerGame->completed_at,
            completedHardcoreAt: $playerGame->completed_hardcore_at,
            points: $playerGame->points,
            pointsHardcore: $playerGame->points_hardcore,

            highestAward: Lazy::create(fn () => static::getHighestAward($playerGame))
        );
    }

    protected static function getHighestAward(PlayerGame $playerGame): ?PlayerBadgeData
    {
        $badges = $playerGame->badges;

        $awardPriority = [
            ['type' => AwardType::Mastery, 'extra' => UnlockMode::Hardcore],    // Mastery
            ['type' => AwardType::Mastery, 'extra' => UnlockMode::Softcore],    // Completion
            ['type' => AwardType::GameBeaten, 'extra' => UnlockMode::Hardcore], // Beaten
            ['type' => AwardType::GameBeaten, 'extra' => UnlockMode::Softcore], // Beaten (softcore)
        ];

        // Loop through the priority list and return the first matching badge.
        foreach ($awardPriority as $criteria) {
            $highestAward = $badges->first(function ($badge) use ($criteria) {
                return $badge->AwardType === $criteria['type'] && $badge->AwardDataExtra === $criteria['extra'];
            });

            if ($highestAward) {
                return PlayerBadgeData::fromPlayerBadge($highestAward);
            }
        }

        // No award was found.
        return null;
    }
}
