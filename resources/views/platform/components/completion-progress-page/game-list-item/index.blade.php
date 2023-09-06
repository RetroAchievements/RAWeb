@props([
    'game' => [],
    'targetUsername' => '',
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
?>

<li class="flex flex-col sm:flex-row w-full sm:justify-between sm:items-center {{ $hasAward ? 'bg-zinc-950/60 light:bg-stone-200' : 'bg-embed' }} pl-2 py-2 pr-4 rounded-sm gap-x-2">
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

        <x-completion-progress-page.game-list-item.primary-meta
            :gameId="$gameId"
            :gameTitle="$game['Title']"
            :numAwarded="$game['NumAwarded']"
            :numPossible="$game['MaxPossible']"
            :firstWonDate="$game['FirstWonDate']"
            :mostRecentWonDate="$game['MostRecentWonDate']"
            :highestAwardKind="$game['HighestAwardKind'] ?? null"
            :highestAwardDate="$game['HighestAwardDate'] ?? null"
        />
    </div>

    <div class="mt-1 sm:mt-0">
        <div class="flex gap-x-2 items-center sm:gap-x-4 sm:divide-x divide-neutral-700 ml-[68px] sm:ml-0">
            <div class="hidden sm:flex gap-x-1 items-center rounded bg-zinc-950 light:bg-zinc-300 py-0.5 px-2">
                <img src="{{ $gameSystemIconSrc }}" width="18" height="18" alt="{{ $consoleName }} console icon">
                <p>{{ $consoleShortName }}</p>
            </div>

            <x-completion-progress-page.game-list-item.progress-bar
                :softcoreCompletionPercentage="$totalCompletionPercentage"
                :hardcoreCompletionPercentage="$hardcoreCompletionPercentage"
                :highestAwardKind="$game['HighestAwardKind'] ?? null"
            />

            <x-completion-progress-page.game-list-item.award-indicator
                :highestAwardKind="$game['HighestAwardKind'] ?? null"
            />
        </div>
    </div>
</li>
