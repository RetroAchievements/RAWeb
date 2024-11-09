@props([
    'achievements' => [],
    'beatenGameCreditDialogContext' => 's:|h:',
    'totalPlayerCount' => 0,
    'isCreditDialogEnabled' => true,
    'showAuthorNames' => false,
    'separateUnlockedAchievements' => true,
])

<?php
if ($separateUnlockedAchievements) {
    $unlockedAchievements = array_filter($achievements, function ($achievement) {
        return !empty($achievement['DateEarned']) || !empty($achievement['DateEarnedHardcore']);
    });

    $lockedAchievements = array_filter($achievements, function ($achievement) {
        return empty($achievement['DateEarned']) && empty($achievement['DateEarnedHardcore']);
    });
} else {
    $unlockedAchievements = [];
    $lockedAchievements = $achievements;
}
?>

@if (count($achievements) > 0)
    <ul id="set-achievements-list" class="flex flex-col">
        @foreach ($unlockedAchievements as $achievement)
            <x-game.achievements-list.achievements-list-item
                :achievement="$achievement"
                :beatenGameCreditDialogContext="$beatenGameCreditDialogContext"
                :isCreditDialogEnabled="$isCreditDialogEnabled"
                :showAuthorName="$showAuthorNames"
                :totalPlayerCount="$totalPlayerCount"
            />
        @endforeach

        @foreach ($lockedAchievements as $achievement)
            <x-game.achievements-list.achievements-list-item
                :achievement="$achievement"
                :beatenGameCreditDialogContext="$beatenGameCreditDialogContext"
                :isCreditDialogEnabled="$isCreditDialogEnabled"
                :showAuthorName="$showAuthorNames"
                :totalPlayerCount="$totalPlayerCount"
            />
        @endforeach
    </ul>
@else
    <p class="sr-only">no achievements</p>
@endif