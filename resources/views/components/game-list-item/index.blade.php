@props([
    'game' => [],
    'targetUsername' => '',

    'isExpandable' => false,
    'isDefaultExpanded' => false,
    'variant' => 'user-progress', // 'user-progress' | 'user-recent-played'
])

<?php

use App\Models\System;

$gameId = $game['GameID'];
$hasAward = isset($game['HighestAwardKind']);

$hardcoreCompletionPercentage = floor($game['PctWonHC'] * 100);
$totalCompletionPercentage = floor($game['PctWon'] * 100);

$consoleId = $game['ConsoleID'];
$consoleName = $game['ConsoleName'];
$consoleShortName = $game['ConsoleNameShort'];
$gameSystemIconSrc = getSystemIconUrl($consoleShortName);

$doesGameHaveAchievements = !!$game['MaxPossible'];
?>

<div
    @if ($isExpandable)
        x-data="{
            isExpanded: {{ $isDefaultExpanded ? 'true' : 'false' }},
            handleToggle() { this.isExpanded = !this.isExpanded; }
        }"
    @endif
    class="relative flex flex-col w-full px-2 py-2 transition-all @if (!$isExpandable) rounded-xs @endif {{ $hasAward ? 'bg-zinc-950/60 light:bg-stone-200' : 'bg-embed' }}"
    :class="{ 'rounded-lg': isExpanded, 'rounded-xs': !isExpanded }"
>
    <div class="flex flex-col sm:flex-row w-full sm:justify-between sm:items-center gap-x-2">
        <div class="flex sm:items-center gap-x-2.5">
            <a href="{{ route('game.show', $gameId) }}">
                <img
                    src="{{ media_asset($game['ImageIcon']) }}"
                    width="58"
                    height="58"
                    class="rounded-xs w-[58px] h-[58px]"
                    loading="lazy"
                    decoding="async"
                    x-data="tooltipComponent($el, {dynamicType: 'game', dynamicId: '{{ $gameId }}', dynamicContext: '{{ $targetUsername }}'})" 
                    @mouseover="showTooltip($event)"
                    @mouseleave="hideTooltip"
                    @mousemove="trackMouseMovement($event)"
                >
            </a>

            <x-game-list-item.primary-meta
                :gameId="$gameId"
                :consoleId="$consoleId"
                :gameTitle="$game['Title']"
                :numAwardedAchievements="$game['NumAwarded']"
                :numPossibleAchievements="$game['MaxPossible']"
                :numAwardedHardcorePoints="$game['ScoreEarnedHardcore'] ?? null"
                :numAwardedCasualPoints="$game['ScoreEarnedCasual'] ?? null"
                :numPossiblePoints="$game['MaxPossibleScore'] ?? null"
                :firstWonDate="$game['FirstWonDate']"
                :mostRecentWonDate="$game['MostRecentWonDate']"
                :highestAwardKind="$game['HighestAwardKind'] ?? null"
                :highestAwardDate="$game['HighestAwardDate'] ?? null"
                :highestAwardTimeTaken="$game['HighestAwardTimeTaken'] ?? null"
                :variant="$variant"
            />
        </div>

        <div class="mt-1 sm:mt-0">
            <div class="flex gap-x-2 items-center sm:gap-x-4 ml-[68px] sm:ml-0 sm:[&>*:not(:first-child)]:border-l sm:[&>*:not(:first-child)]:border-neutral-700">
                @php
                    $consoleTag = $variant === 'user-recently-played' ? 'a' : 'div';
                @endphp
                <{{ $consoleTag }}
                    @if ($variant === 'user-recently-played') href="{{ route('user.completion-progress', ['user' => $targetUsername, 'filter[system]' => $consoleId]) }}" @endif
                    class="hidden sm:flex gap-x-1 items-center rounded-sm bg-zinc-950 light:bg-zinc-300 py-0.5 px-2"
                >
                    <img src="{{ $gameSystemIconSrc }}" width="18" height="18" alt="{{ $consoleName }} console icon">
                    <p>{{ $consoleShortName }}</p>
                </{{ $consoleTag }}>

                <x-game-list-item.progress-bar
                    :casualCompletionPercentage="$totalCompletionPercentage"
                    :hardcoreCompletionPercentage="$hardcoreCompletionPercentage"
                    :numPossible="$game['MaxPossible']"
                    :hasAward="isset($game['HighestAwardKind'])"
                />

                <x-game-list-item.award-indicator
                    :highestAwardKind="$game['HighestAwardKind'] ?? null"
                />

                @if ($isExpandable)
                    <div class="absolute sm:static top-0 right-0 sm:pl-4">
                        <button
                            @click="handleToggle"
                            class="btn @if ($doesGameHaveAchievements) transition-transform lg:active:scale-95 duration-75 @endif"
                            @if (!$doesGameHaveAchievements) disabled @endif
                        >
                            <div
                                class="transition-transform @if ($isDefaultExpanded) rotate-180 @endif"
                                :class="{ 'rotate-180': isExpanded }"
                            >
                                <x-fas-chevron-down />
                            </div>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($isExpandable)
        <div
            @if (!$isDefaultExpanded) x-cloak @endif
            x-show="isExpanded"
            x-transition:enter="ease-in-out duration-300"
            x-transition:enter-start="opacity-0 max-h-0 transform-[translateY(-0.375rem)] overflow-hidden"
            x-transition:enter-end="opacity-100 max-h-[1000px] transform-[translateY(0)] overflow-hidden"
            x-transition:leave="ease-in-out duration-200"
            x-transition:leave-start="opacity-100 max-h-[1000px] overflow-hidden"
            x-transition:leave-end="opacity-0 max-h-0 overflow-hidden"
            class="transition-all will-change-transform"
        >
            <hr class="mt-2 border-embed-highlight">

            <div class="py-4 @if ($variant === 'user-recently-played') place-content-center grid grid-cols-[repeat(auto-fill,minmax(52px,52px))] px-0.5 sm:px-4 @endif">
                {{ $slot }}
            </div>
        </div>
    @endif
</div>