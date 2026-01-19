<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Community\Actions\BuildActivePlayersAction;
use App\Community\Actions\BuildThinRecentForumPostsDataAction;
use App\Community\Actions\FetchGameActivityDataAction;
use App\Community\Enums\AwardType;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\GameActivitySnapshotType;
use App\Community\Enums\NewsCategory;
use App\Data\StaticDataData;
use App\Enums\Permissions;
use App\Http\Actions\BuildAchievementOfTheWeekDataAction;
use App\Http\Actions\BuildCurrentlyOnlineDataAction;
use App\Http\Actions\BuildHomePageClaimsDataAction;
use App\Http\Actions\BuildMostRecentGameAwardDataAction;
use App\Http\Actions\BuildNewsDataAction;
use App\Http\Actions\BuildSiteReleaseNotesAction;
use App\Http\Actions\BuildUserCurrentGameDataAction;
use App\Http\Actions\CheckHasUnreadSiteReleaseNoteAction;
use App\Http\Controller;
use App\Http\Data\HomePagePropsData;
use App\Models\News;
use App\Models\StaticData;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class HomeController extends Controller
{
    public function index(
        Request $request,
        BuildAchievementOfTheWeekDataAction $buildAchievementOfTheWeekData,
        BuildMostRecentGameAwardDataAction $buildMostRecentGameAwardData,
        BuildNewsDataAction $buildNewsData,
        BuildCurrentlyOnlineDataAction $buildCurrentlyOnlineData,
        BuildActivePlayersAction $buildActivePlayers,
        FetchGameActivityDataAction $fetchGameActivityData,
        BuildHomePageClaimsDataAction $buildHomePageClaimsData,
        BuildThinRecentForumPostsDataAction $buildThinRecentForumPostsData,
        BuildUserCurrentGameDataAction $buildUserCurrentGameData,
        BuildSiteReleaseNotesAction $buildSiteReleaseNotes,
        CheckHasUnreadSiteReleaseNoteAction $checkHasUnreadSiteReleaseNote,
    ): InertiaResponse {
        /** @var ?User $user */
        $user = Auth::user();

        $staticData = StaticData::first();
        $staticDataData = StaticDataData::fromStaticData($staticData);

        $achievementOfTheWeek = $buildAchievementOfTheWeekData->execute($user);
        $mostRecentGameMastered = $buildMostRecentGameAwardData->execute($staticData, AwardType::Mastery);
        $mostRecentGameBeaten = $buildMostRecentGameAwardData->execute($staticData, AwardType::GameBeaten);
        $recentNews = $buildNewsData->execute();
        $completedClaims = $buildHomePageClaimsData->execute(ClaimStatus::Complete, 6);
        $newClaims = $buildHomePageClaimsData->execute(ClaimStatus::Active, 5);
        $currentlyOnline = $buildCurrentlyOnlineData->execute();

        $persistedActivePlayersSearch = $request->cookie('active_players_search');
        $activePlayers = $buildActivePlayers->execute(perPage: 20, search: $persistedActivePlayersSearch);
        $trendingGames = $fetchGameActivityData->execute(GameActivitySnapshotType::Trending);
        $popularGames = $fetchGameActivityData->execute(GameActivitySnapshotType::Popular);

        $permissions = $user ? (int) $user->getAttribute('Permissions') : Permissions::Unregistered;
        $recentForumPosts = $buildThinRecentForumPostsData->execute(
            permissions: $permissions,
        );

        $userCurrentGameData = $buildUserCurrentGameData->execute($user);
        $hasUnreadSiteReleaseNote = $checkHasUnreadSiteReleaseNote->execute($user);

        $props = new HomePagePropsData(
            staticData: $staticDataData,
            achievementOfTheWeek: $achievementOfTheWeek,
            mostRecentGameMastered: $mostRecentGameMastered,
            mostRecentGameBeaten: $mostRecentGameBeaten,
            recentNews: $recentNews,
            completedClaims: $completedClaims,
            currentlyOnline: $currentlyOnline,
            newClaims: $newClaims,
            activePlayers: $activePlayers,
            trendingGames: $trendingGames,
            popularGames: $popularGames,
            recentForumPosts: $recentForumPosts,
            persistedActivePlayersSearch: $persistedActivePlayersSearch,
            userCurrentGame: $userCurrentGameData[0] ?? null,
            userCurrentGameMinutesAgo: $userCurrentGameData[1] ?? null,
            hasSiteReleaseNotes: News::where('category', NewsCategory::SiteReleaseNotes)->exists(),
            hasUnreadSiteReleaseNote: $hasUnreadSiteReleaseNote,
            deferredSiteReleaseNotes: Inertia::defer(fn () => $buildSiteReleaseNotes->execute()),
        );

        return Inertia::render('index', $props);
    }

    public function store(): void
    {
    }

    public function show(): void
    {
    }

    public function update(): void
    {
    }

    public function destroy(): void
    {
    }
}
