<?php

declare(strict_types=1);

namespace App\Http\Data;

use App\Data\AchievementSetClaimData;
use App\Data\CurrentlyOnlineData;
use App\Data\ForumTopicData;
use App\Data\NewsData;
use App\Data\StaticDataData;
use App\Data\StaticGameAwardData;
use App\Platform\Data\EventAchievementData;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('HomePageProps')]
class HomePagePropsData extends Data
{
    /**
     * @param Collection<int, NewsData> $recentNews
     * @param Collection<int, AchievementSetClaimData> $completedClaims
     * @param Collection<int, AchievementSetClaimData> $newClaims
     * @param Collection<int, ForumTopicData> $recentForumPosts
     */
    public function __construct(
        public StaticDataData $staticData,
        public ?EventAchievementData $achievementOfTheWeek,
        public ?StaticGameAwardData $mostRecentGameMastered,
        public ?StaticGameAwardData $mostRecentGameBeaten,
        public Collection $recentNews,
        public Collection $completedClaims,
        public CurrentlyOnlineData $currentlyOnline,
        public Collection $newClaims,
        public Collection $recentForumPosts,
    ) {
    }
}
