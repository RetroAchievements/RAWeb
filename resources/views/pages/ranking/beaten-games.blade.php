<?php

use App\Enums\UserPreference;
use App\Models\MessageThread;
use App\Models\User;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Carbon;
use function Laravel\Folio\{name};

name('ranking.beaten-games');

?>

@props([
    'allSystems' => [],
    'gameKindFilterOptions' => [],
    'isUserOnCurrentPage' => false,
    'leaderboardKind' => 'retail',
    'paginator' => null,
    'selectedConsoleId' => null,
    'targetUserRankingData' => null,
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
