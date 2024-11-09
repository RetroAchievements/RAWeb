<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\StaticData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('StaticData')]
class StaticDataData extends Data
{
    public function __construct(
        public int $numGames,
        public int $numAchievements,
        public int $numHardcoreMasteryAwards,
        public int $numHardcoreGameBeatenAwards,
        public int $numRegisteredUsers,
        public int $numAwarded,
        public int $totalPointsEarned,
        public ?int $eventAotwForumId,
    ) {
    }

    public static function fromStaticData(?StaticData $staticData): self
    {
        return new self(
            numGames: $staticData->NumGames ?? 0,
            numAchievements: $staticData->NumAchievements ?? 0,
            numHardcoreMasteryAwards: $staticData->num_hardcore_mastery_awards ?? 0,
            numHardcoreGameBeatenAwards: $staticData->num_hardcore_game_beaten_awards ?? 0,
            numRegisteredUsers: $staticData->NumRegisteredUsers ?? 0,
            numAwarded: $staticData->NumAwarded ?? 0,
            totalPointsEarned: $staticData->TotalPointsEarned ?? 0,
            eventAotwForumId: $staticData->Event_AOTW_ForumID ?? null,
        );
    }
}
