<?php

use App\Platform\Services\BeatenGamesLeaderboardService;
use Illuminate\View\View;

use function Laravel\Folio\{name, render};

name('ranking.beaten-games');

render(function (View $view, BeatenGamesLeaderboardService $pageService) {
    $viewData = $pageService->buildViewData(request());

    if (isset($viewData['redirect'])) {
        return redirect($viewData['redirect']);
    }

    return $view->with($viewData);
});

?>

<x-app-layout
    pageTitle="Beaten Games Leaderboard"
    pageDescription="Where do you rank? Dive into detailed rankings, filter by console, and discover top players on our Beaten Games Leaderboard."
>
    <h1>Beaten Games Leaderboard</h1>

    <x-beaten-games-leaderboard.meta-panel
        :allSystems="$allSystems"
        :gameKindFilterOptions="$gameKindFilterOptions"
        :leaderboardKind="$leaderboardKind"
        :selectedConsoleId="$selectedConsoleId"
    />

    @if ($paginator->isEmpty())
        <div class="w-full flex flex-col gap-y-2 items-center justify-center bg-embed rounded py-8">
            <img src="/assets/images/cheevo/confused.webp" alt="No leaderboard rows">
            <p>There aren't any rows matching your current filter criteria.</p>
        </div>
    @else
        <div class="mb-4">
            <x-beaten-games-leaderboard.leaderboard-table
                :isUserOnCurrentPage="$isUserOnCurrentPage"
                :paginator="$paginator"
                :targetUserRankingData="$targetUserRankingData"
            />
        </div>

        <x-beaten-games-leaderboard.pagination-controls
            :paginator="$paginator"
            :userPageNumber="$userPageNumber"
        />
    @endif
</x-app-layout>
