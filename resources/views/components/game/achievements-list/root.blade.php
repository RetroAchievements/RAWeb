@props([
    'achievements' => [],
    'beatenGameCreditDialogContext' => 's:|h:',
    'isCreditDialogEnabled' => true,
    'separateUnlockedAchievements' => true,
    'shouldHideUnlocked' => false,
    'showAuthorNames' => false,
    'totalPlayerCount' => 0,
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
                :shouldHideUnlocked="$shouldHideUnlocked"
                :showAuthorName="$showAuthorNames"
                :totalPlayerCount="$totalPlayerCount"
            />
        @endforeach

        @foreach ($lockedAchievements as $achievement)
            <x-game.achievements-list.achievements-list-item
                :achievement="$achievement"
                :beatenGameCreditDialogContext="$beatenGameCreditDialogContext"
                :isCreditDialogEnabled="$isCreditDialogEnabled"
                :shouldHideUnlocked="$shouldHideUnlocked"
                :showAuthorName="$showAuthorNames"
                :totalPlayerCount="$totalPlayerCount"
            />
        @endforeach
    </ul>
@else
    <p class="sr-only">no achievements</p>
@endif