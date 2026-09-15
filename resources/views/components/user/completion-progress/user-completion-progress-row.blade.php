@props([
    'completionProgressEntity' => [],
])

<?php
$numAwarded = $completionProgressEntity['NumAwarded'] ?? 0;
$numAwardedHardcore = $completionProgressEntity['NumAwardedHC'] ?? 0;
$maxPossible = $completionProgressEntity['MaxPossible'];

$highestAwardKind = $completionProgressEntity['HighestAwardKind'] ?? 'unfinished';
?>

<tr class="w-full">
    <td class="py-2">
        <x-game.multiline-avatar
            :gameId="$completionProgressEntity['GameID']"
            :gameTitle="$completionProgressEntity['Title']"
            :gameImageIcon="$completionProgressEntity['ImageIcon']"
            :consoleName="$completionProgressEntity['ConsoleName']"
        />
    </td>

    <td class="min-w-[112px]" width="112px" style="padding-top: 0">
        <div class="mt-2 mb-0.5">
            <x-game-progress-bar
                :casualProgress="$numAwarded"
                :hardcoreProgress="$numAwardedHardcore"
                :maxProgress="$maxPossible"
                :awardIndicator="$highestAwardKind"
            />
        </div>

        <p class="pr-5 text-center text-2xs -mt-1.5">
            {{ $numAwarded }} of {{ $maxPossible }}
        </p>
    </td>
</tr>
