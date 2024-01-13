@props([
    'completionProgressEntity' => [],
])

<?php
$numAwarded = $completionProgressEntity['NumAwarded'] ?? 0;
$maxPossible = $completionProgressEntity['MaxPossible'];

$prefersHiddenUserCompletedSets = request()->cookie('prefers_hidden_user_completed_sets') === 'true';

$rowClassNames = 'w-full';
if ($numAwarded === $maxPossible) {
    $rowClassNames .= ' completion-progress-completed-row';

    if ($prefersHiddenUserCompletedSets) {
        $rowClassNames .= ' hidden';
    }
}
?>

<tr class="{{ $rowClassNames }}">
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
                :softcoreProgress="$numAwarded"
                :hardcoreProgress="$completionProgressEntity['NumAwardedHC'] ?? 0"
                :maxProgress="$maxPossible"
                :awardIndicator="$completionProgressEntity['HighestAwardKind'] ?? 'unfinished'"
            />
        </div>

        <p class="pr-5 text-center text-2xs">
            {{ $numAwarded }} of {{ $maxPossible }}
        </p>
    </td>
</tr>
