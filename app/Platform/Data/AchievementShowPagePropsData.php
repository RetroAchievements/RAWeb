<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Data\CommentData;
use App\Data\UserPermissionsData;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AchievementShowPageProps')]
class AchievementShowPagePropsData extends Data
{
    /**
     * @param Collection<int, CommentData> $recentVisibleComments
     * @param AchievementData[]|null $proximityAchievements
     */
    public function __construct(
        public AchievementData $achievement,
        public UserPermissionsData $can,
        public bool $isSubscribedToComments,
        public int $numComments,
        public Collection $recentVisibleComments,
        public ?GameData $backingGame = null,
        public ?GameAchievementSetData $gameAchievementSet = null,
        public ?array $proximityAchievements = null,
        public int $promotedAchievementCount = 0,
    ) {
    }
}
