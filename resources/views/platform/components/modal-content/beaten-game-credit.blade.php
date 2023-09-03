@props([
    'gameTitle' => '',
    'progressionAchievements' => [],
    'winConditionAchievements' => [],
    'unlockContext' => 's:|h:',
])

<?php
[$softcoreUnlocks, $hardcoreUnlocks] = explode("|", $unlockContext);
$softcoreIds = explode(",", substr($softcoreUnlocks, 2)); // skip "s:"
$hardcoreIds = explode(",", substr($hardcoreUnlocks, 2)); // skip "h:"

$numProgressionAchievements = count($progressionAchievements);
$numWinConditionAchievements = count($winConditionAchievements);
?>

<div>
    <div class="grid gap-y-3 mb-6">
        <p>
            A game is considered beaten when you've unlocked <span class="font-bold">ALL</span> of its
            progression achievements and <span class="font-bold">ANY</span> of its win condition achievements.
        </p>

        <p>
            {{ $gameTitle }} has {{ $numProgressionAchievements }} progression
            {{ trans_choice(__('resource.achievement.title'), $numProgressionAchievements) }} and
            {{ $numWinConditionAchievements }} win condition
            {{ trans_choice(__('resource.achievement.title'), $numWinConditionAchievements) }}.
        </p>
    </div>

    @if ($numProgressionAchievements > 0)
        <div class="flex items-center gap-x-2.5 mb-4">
            <div class="w-7 h-7 text-neutral-200" aria-label="Progression icon"><x-icon.progression /></div>
            <p class="text-lg">You need ALL of these:</p>
        </div>

        @foreach ($progressionAchievements as $progressionAchievement)
            <ul class="flex flex-col even:bg-bg">
                <x-game.achievements-list.achievements-list-item
                    :achievement="$progressionAchievement"
                    :useMinimalLayout="true"
                    isUnlocked="{{ in_array($progressionAchievement['ID'], $softcoreIds) }}"
                    isUnlockedHardcore="{{ in_array($progressionAchievement['ID'], $hardcoreIds) }}"
                />
            </ul>
        @endforeach
    @endif

    @if ($numWinConditionAchievements > 0)
        <div class="flex items-center gap-x-2.5 mt-12 mb-4">
            <div class="w-7 h-7 text-neutral-200" aria-label="Progression icon"><x-icon.win-condition /></div>
            <p class="text-lg">
                You
                {{ $numProgressionAchievements > 0 && $numWinConditionAchievements > 0 ? "also" : "" }}
                need {{ $numWinConditionAchievements > 1 ? "ANY of these": "this" }}:
            </p>
        </div>

        @foreach ($winConditionAchievements as $winConditionAchievement)
            <ul class="flex flex-col even:bg-bg">
                <x-game.achievements-list.achievements-list-item
                    :achievement="$winConditionAchievement"
                    :useMinimalLayout="true"
                    isUnlocked="{{ in_array($winConditionAchievement['ID'], $softcoreIds) }}"
                    isUnlockedHardcore="{{ in_array($winConditionAchievement['ID'], $hardcoreIds) }}"
                />
            </ul>
        @endforeach
    @endif
</div>