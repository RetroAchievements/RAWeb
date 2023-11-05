@props([
    'game' => [],
    'targetUsername' => '',

    'isExpandable' => false,
    'isDefaultExpanded' => false,
    'variant' => 'user-progress', // 'user-progress' | 'user-recent-played'
])

<?php
$gameId = $game['GameID'];
$consoleId = $game['ConsoleID'];
$consoleName = config('systems')[$consoleId]['name'];
$consoleShortName = config('systems')[$consoleId]['name_short'];
$hasAward = isset($game['HighestAwardKind']);

$hardcoreCompletionPercentage = round($game['PctWonHC'] * 100);
$totalCompletionPercentage = round($game['PctWon'] * 100);

$gameSystemIconSrc = getSystemIconUrl($consoleId);

$doesGameHaveAchievements = !!$game['MaxPossible'];
$isInitiallyExpanded = $isDefaultExpanded && $doesGameHaveAchievements;
?>

<div
    @if ($isExpandable)
        x-data="{
            isExpanded: {{ $isInitiallyExpanded ? 'true' : 'false' }},
            handleToggle() { this.isExpanded = !this.isExpanded; }
        }"
    @endif
    class="relative flex flex-col w-full pl-2 py-2 pr-4 rounded-sm {{ $hasAward ? 'bg-zinc-950/60 light:bg-stone-200' : 'bg-embed' }}"
>
    <div class="flex flex-col sm:flex-row w-full sm:justify-between sm:items-center gap-x-2">
        <div class="flex sm:items-center gap-x-2.5">
            <a href="{{ route('game.show', $gameId) }}">
                <img
                    src="{{ media_asset($game['ImageIcon']) }}"
                    width="58"
                    height="58"
                    class="rounded-sm w-[58px] h-[58px]"
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
                :gameTitle="$game['Title']"
                :numAwardedAchievements="$game['NumAwarded']"
                :numPossibleAchievements="$game['MaxPossible']"
                :numAwardedHardcorePoints="$game['ScoreEarnedHardcore'] ?? null"
                :numAwardedSoftcorePoints="$game['ScoreEarnedSoftcore'] ?? null"
                :numPossiblePoints="$game['MaxPossibleScore'] ?? null"
                :firstWonDate="$game['FirstWonDate']"
                :mostRecentWonDate="$game['MostRecentWonDate']"
                :highestAwardKind="$game['HighestAwardKind'] ?? null"
                :highestAwardDate="$game['HighestAwardDate'] ?? null"
                :variant="$variant"
            />
        </div>

        <div class="mt-1 sm:mt-0">
            <div class="flex gap-x-2 items-center sm:gap-x-4 sm:divide-x divide-neutral-700 ml-[68px] sm:ml-0">
                <div class="hidden sm:flex gap-x-1 items-center rounded bg-zinc-950 light:bg-zinc-300 py-0.5 px-2">
                    <img src="{{ $gameSystemIconSrc }}" width="18" height="18" alt="{{ $consoleName }} console icon">
                    <p>{{ $consoleShortName }}</p>
                </div>

                <x-game-list-item.progress-bar
                    :softcoreCompletionPercentage="$totalCompletionPercentage"
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
                                class="transition-transform @if ($isInitiallyExpanded) rotate-180 @endif"
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
            @if (!$isDefaultExpanded ) x-cloak @endif
            x-show="isExpanded"
            x-transition:enter="ease-out duration-150"
            x-transition:enter-start="opacity-0 h-0 translate-y-[-0.5rem]"
            x-transition:enter-end="opacity-1 h-fit translate-y-0"
            x-transition:leave="hidden"
        >
            <hr class="mt-2 border-embed-highlight">

            <div class="py-4 @if ($variant === 'user-recently-played') flex flex-wrap -mx-1 sm:mx-0 sm:px-4 @endif">
                {{ $slot }}
            </div>
        </div>
    @endif
</div>