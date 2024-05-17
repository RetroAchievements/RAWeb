<?php

use App\Models\User;
use App\Platform\Services\PlayerCompletionProgressPageService;
use Illuminate\View\View;

use function Laravel\Folio\{name, middleware, render};

name('user.completion-progress');
middleware('can:view,user');

render(function (View $view, User $user, PlayerCompletionProgressPageService $pageService) {
    return $view->with($pageService->buildViewData(request(), $user));
});

?>

@props([
    'allAvailableConsoleIds' => [],
    'completedGamesList' => [],
    'currentPage' => 1,
    'isFiltering' => false,
    'isMe' => false,
    'me' => null, // ?User
    'milestones' => [],
    'primaryCountsMetrics' => [],
    'selectedConsoleId' => 0,
    'selectedSortOrder' => 'unlock_date',
    'selectedStatus' => null, // ?string
    'seo' => [],
    'siteAwards' => [],
    'targetUsername' => '',
    'totalInList' => 0,
    'totalPages' => 1,
    'user' => null, // User
])

<x-app-layout
    :pageTitle="$seo['pageTitle']"
    :pageDescription="$seo['pageDescription']"
>
    <div>
        <x-user.breadcrumbs :targetUsername="$targetUsername" currentPage="Completion Progress" />

        <div class="mt-3 -mb-3 w-full flex gap-x-3">
            {!! userAvatar($targetUsername, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
            <h1 class="mt-[10px] w-full">{{ $seo['pageTitle'] }}</h1>
        </div>

        <x-completion-progress-page.meta-panel
            :availableConsoleIds="$allAvailableConsoleIds"
            :selectedConsoleId="$selectedConsoleId"
            :selectedSortOrder="$selectedSortOrder"
            :selectedStatus="$selectedStatus"
        />

        <div class="mb-4">
            <x-completion-progress-page.awards-jumbobox
                :playedCount="$primaryCountsMetrics['numPlayed']"
                :unfinishedCount="$primaryCountsMetrics['numUnfinished']"
                :beatenSoftcoreCount="$primaryCountsMetrics['numBeatenSoftcore']"
                :beatenHardcoreCount="$primaryCountsMetrics['numBeatenHardcore']"
                :completedCount="$primaryCountsMetrics['numCompleted']"
                :masteredCount="$primaryCountsMetrics['numMastered']"
            />
        </div>

        <div class="w-full flex justify-between items-center mb-2">
            @if ($totalInList > 0)
                <p class="text-xs">
                    Viewing
                    <span class="font-bold">{{ localized_number($totalInList) }}</span>
                    @if ($isFiltering)
                        of {{ localized_number($primaryCountsMetrics['numPlayed']) }}
                    @endif
                    {{ trans_choice(__('resource.game.title'), $isFiltering ? $primaryCountsMetrics['numPlayed'] : $totalInList) }}
                </p>
            @else
                <span></span>
            @endif

            @if ($isFiltering || $selectedSortOrder !== 'unlock_date')
                <a href="{{ route('user.completion-progress', ['user' => $targetUsername]) }}" class="btn flex items-center gap-x-0.5 transition lg:active:scale-95">
                    <x-fas-undo />
                    <span>Reset filters/sort</span>
                </a>
            @else
                <span></span>
            @endif
        </div>

        @if ($totalInList === 0)
            <div class="w-full flex flex-col gap-y-2 items-center justify-center bg-embed rounded py-8">
                <img src="/assets/images/cheevo/confused.webp" alt="No sets in progress">
                <p>
                    {{ $isMe ? "You don't" : $targetUsername . " doesn't" }}
                    have any
                    {{ $isFiltering ? "games matching your current filter criteria." : "games with achievement unlocks yet." }}
                </p>
            </div>
        @endif

        <x-completion-progress-page.game-list :completedGamesList="$completedGamesList" :targetUsername="$targetUsername" />

        @if ($totalPages > 0)
            <div class="w-full flex justify-end mt-2">
                <x-paginator :totalPages="$totalPages" :currentPage="$currentPage" />
            </div>
        @endif
    </div>

    <x-slot name="sidebar">
        <x-completion-progress-page.milestones
            :milestones="$milestones"
            :isFiltering="$isFiltering"
            :isMe="$isMe"
            :targetUsername="$targetUsername"
        />
    </x-slot>
</x-app-layout>
