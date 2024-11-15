<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Community\Actions\BuildThinRecentForumPostsDataAction;
use App\Community\Enums\AwardType;
use App\Community\Enums\ClaimStatus;
use App\Data\StaticDataData;
use App\Enums\Permissions;
use App\Http\Actions\BuildAchievementOfTheWeekDataAction;
use App\Http\Actions\BuildCurrentlyOnlineDataAction;
use App\Http\Actions\BuildHomePageClaimsDataAction;
use App\Http\Actions\BuildMostRecentGameAwardDataAction;
use App\Http\Actions\BuildNewsDataAction;
use App\Http\Controller;
use App\Http\Data\HomePagePropsData;
use App\Models\StaticData;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class HomeController extends Controller
{
    public function index(
        BuildAchievementOfTheWeekDataAction $buildAchievementOfTheWeekData,
        BuildMostRecentGameAwardDataAction $buildMostRecentGameAwardData,
        BuildNewsDataAction $buildNewsData,
        BuildCurrentlyOnlineDataAction $buildCurrentlyOnlineData,
        BuildHomePageClaimsDataAction $buildHomePageClaimsData,
        BuildThinRecentForumPostsDataAction $buildThinRecentForumPostsData,
    ): InertiaResponse {
        $staticData = StaticData::first();

        $staticDataData = StaticDataData::fromStaticData($staticData);

        $achievementOfTheWeek = $buildAchievementOfTheWeekData->execute($staticData);
        $mostRecentGameMastered = $buildMostRecentGameAwardData->execute($staticData, AwardType::Mastery);
        $mostRecentGameBeaten = $buildMostRecentGameAwardData->execute($staticData, AwardType::GameBeaten);
        $recentNews = $buildNewsData->execute();
        $completedClaims = $buildHomePageClaimsData->execute(ClaimStatus::Complete, 6);
        $newClaims = $buildHomePageClaimsData->execute(ClaimStatus::Active, 5);
        $currentlyOnline = $buildCurrentlyOnlineData->execute();

        /** @var ?User $user */
        $user = Auth::user();
        $permissions = $user ? (int) $user->getAttribute('Permissions') : Permissions::Unregistered;
        $recentForumPosts = $buildThinRecentForumPostsData->execute(
            permissions: $permissions,
        );

        $props = new HomePagePropsData(
            staticData: $staticDataData,
            achievementOfTheWeek: $achievementOfTheWeek,
            mostRecentGameMastered: $mostRecentGameMastered,
            mostRecentGameBeaten: $mostRecentGameBeaten,
            recentNews: $recentNews,
            completedClaims: $completedClaims,
            currentlyOnline: $currentlyOnline,
            newClaims: $newClaims,
            recentForumPosts: $recentForumPosts,
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
