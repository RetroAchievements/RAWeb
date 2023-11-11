@props([
    'allSystems' => [],
    'gameKindFilterOptions' => [],
    'isUserOnCurrentPage' => false,
    'myRankingData' => null,
    'myUsername' => null,
    'paginator' => null,
    'selectedConsoleId' => null,
    'userPageNumber' => null,
])

<x-app-layout
    pageTitle="Beaten Games Leaderboard"
    pageDescription="Where do you rank? Dive into detailed rankings, filter by console, and discover top players on our Beaten Games Leaderboard."
>
    <h1>Beaten Games Leaderboard</h1>

    <x-beaten-games-leaderboard.meta-panel
        :allSystems="$allSystems"
        :gameKindFilterOptions="$gameKindFilterOptions"
        :isCurrentSystemCacheable="$isCurrentSystemCacheable"
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
                :myRankingData="$myRankingData"
                :myUsername="$myUsername"
                :paginator="$paginator"
            />
        </div>

        <x-beaten-games-leaderboard.pagination-controls
            :paginator="$paginator"
            :userPageNumber="$userPageNumber"
        />
    @endif
</x-app-layout>