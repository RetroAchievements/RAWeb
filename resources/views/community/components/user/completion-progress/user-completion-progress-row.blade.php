@props([
    'completionProgressEntity' => [],
])

<?php
$numAwarded = $completionProgressEntity['NumAwarded'] ?? 0;
$numAwardedHardcore = $completionProgressEntity['NumAwardedHC'] ?? 0;
$maxPossible = $completionProgressEntity['MaxPossible'];

$isBeatenHardcore = in_array('beaten-hardcore', $completionProgressEntity['AllAwardKinds'] ?? []);
$isBeatenSoftcore = in_array('beaten-softcore', $completionProgressEntity['AllAwardKinds'] ?? []);

$prefersHiddenUserCompletedSets = request()->cookie('prefers_hidden_user_completed_sets') === 'true';

$rowClassNames = 'w-full';
if ($numAwarded === $maxPossible) {
    $rowClassNames .= ' completion-progress-completed-row';

    if ($prefersHiddenUserCompletedSets) {
        $rowClassNames .= ' hidden';
    }
}

$highestAwardKind = $completionProgressEntity['HighestAwardKind'] ?? 'unfinished';
if ($highestAwardKind === 'mastered' && $numAwardedHardcore !== $maxPossible) {
    if ($numAwarded === $maxPossible) {
        $highestAwardKind = 'completed';
    } else if ($isBeatenHardcore) {
        $highestAwardKind = 'beaten-hardcore';
    } else if ($isBeatenSoftcore) {
        $highestAwardKind = 'beaten-softcore';
    }
}
if ($highestAwardKind === 'completed' && $numAwarded !== $maxPossible) {
    if ($isBeatenHardcore) {
        $highestAwardKind = 'beaten-hardcore';
    } else if ($isBeatenSoftcore) {
        $highestAwardKind = 'beaten-softcore';
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
