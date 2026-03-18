<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Data\CommentData;
use App\Data\UserPermissionsData;
use App\Platform\Enums\AchievementPageTab;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\AutoInertiaDeferred;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AchievementShowPageProps')]
class AchievementShowPagePropsData extends Data
{
    /**
     * @param Collection<int, CommentData> $recentVisibleComments
     * @param AchievementChangelogEntryData[] $changelog
     * @param AchievementData[]|null $proximityAchievements
     * @param Collection<int, AchievementRecentUnlockData> $recentUnlocks
     */
    public function __construct(
        public AchievementData $achievement,
        public UserPermissionsData $can,
        public bool $isSubscribedToComments,
        public int $numComments,
        public Collection $recentVisibleComments,
        public ?GameData $backingGame = null,
        public ?GameAchievementSetData $gameAchievementSet = null,
        public array $changelog = [],
        public ?array $proximityAchievements = null,
        public int $promotedAchievementCount = 0,
        #[AutoInertiaDeferred]
        public Lazy|Collection $recentUnlocks = new Collection(),
        public AchievementPageTab $initialTab = AchievementPageTab::Comments,
    ) {
    }
}
