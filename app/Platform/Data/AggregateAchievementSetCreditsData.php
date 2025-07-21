<?php

declare(strict_types=1);

namespace App\Platform\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AggregateAchievementSetCredits')]
class AggregateAchievementSetCreditsData extends Data
{
    /**
     * @param UserCreditsData[] $achievementsAuthors
     * @param UserCreditsData[] $achievementsMaintainers
     * @param UserCreditsData[] $achievementsArtwork
     * @param UserCreditsData[] $achievementsDesign
     * @param UserCreditsData[] $achievementSetArtwork
     * @param UserCreditsData[] $achievementsLogic
     * @param UserCreditsData[] $achievementsTesting
     * @param UserCreditsData[] $achievementsWriting
     * @param UserCreditsData[] $hashCompatibilityTesting
     */
    public function __construct(
        public array $achievementsAuthors,
        public array $achievementsMaintainers,
        public array $achievementsArtwork,
        public array $achievementsDesign,
        public array $achievementSetArtwork,
        public array $achievementsLogic,
        public array $achievementsTesting,
        public array $achievementsWriting,
        public array $hashCompatibilityTesting,
    ) {
    }
}
