<?php
$highlightedRank = $myRankingData ? $myRankingData['userRank'] : null;
$isHighlightedRankOnCurrentPage = 
    $highlightedRank
    && ($highlightedRank >= $startingRank)
    && ($highlightedRank < ($startingRank + count($paginator)));

$userPageNumber = $myRankingData ? (int) ceil($myRankingData['userRank'] / $paginator->perPage()) : null;
?>

<x-app-layout
    pageTitle="Beaten Games Leaderboard"
    pageDescription="Where do you rank? Dive into detailed rankings, filter by console, and discover top players on our Beaten Game Leaderboard."
>
    <h1>Beaten Games Leaderboard</h1>

    <x-beaten-games-leaderboard.meta-panel
        :allSystems="$allSystems"
        :gameKindFilterOptions="$gameKindFilterOptions"
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
                :highlightedRank="$highlightedRank"
                :isHighlightedRankOnCurrentPage="$isHighlightedRankOnCurrentPage"
                :myRankingData="$myRankingData"
                :paginator="$paginator"
                :startingRank="$startingRank"
            />
        </div>

        <x-beaten-games-leaderboard.pagination-controls
            :isHighlightedRankOnCurrentPage="$isHighlightedRankOnCurrentPage"
            :paginator="$paginator"
            :userPageNumber="$userPageNumber"
        />
    @endif
</x-app-layout>