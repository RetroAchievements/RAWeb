@props([
    'achievements' => [],
    'beatenGameCreditDialogContext' => 's:|h:',
    'totalPlayerCount' => 0,
    'progressionTypeValue' => 'progression', // `AchievementType`
    'winConditionTypeValue' => 'win_condition', // `AchievementType`
    'isCreditDialogEnabled' => true,
])

<?php
$unlockedAchievements = array_filter($achievements, function ($achievement) {
    return !empty($achievement['DateEarned']) || !empty($achievement['DateEarnedHardcore']);
});

$lockedAchievements = array_filter($achievements, function ($achievement) {
    return empty($achievement['DateEarned']) && empty($achievement['DateEarnedHardcore']);
});

$winConditionAchievements = array_filter($achievements, function ($achievement) use ($winConditionTypeValue) {
    return isset($achievement['type']) && $achievement['type'] === $winConditionTypeValue;
});
?>

@if (count($achievements) > 0)
    <ul class="flex flex-col">
        @foreach ($unlockedAchievements as $achievement)
            <x-game.achievements-list.achievements-list-item
                :achievement="$achievement"
                :totalPlayerCount="$totalPlayerCount"
                :progressionTypeValue="$progressionTypeValue"
                :winConditionTypeValue="$winConditionTypeValue"
                :beatenGameCreditDialogContext="$beatenGameCreditDialogContext"
                :isCreditDialogEnabled="$isCreditDialogEnabled"
            />
        @endforeach

        @foreach ($lockedAchievements as $achievement)
            <x-game.achievements-list.achievements-list-item
                :achievement="$achievement"
                :totalPlayerCount="$totalPlayerCount"
                :progressionTypeValue="$progressionTypeValue"
                :winConditionTypeValue="$winConditionTypeValue"
                :beatenGameCreditDialogContext="$beatenGameCreditDialogContext"
                :isCreditDialogEnabled="$isCreditDialogEnabled"
            />
        @endforeach
    </ul>
@else
    <p class="sr-only">no achievements</p>
@endif