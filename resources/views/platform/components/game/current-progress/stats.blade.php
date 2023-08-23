@props([
    'totalAchievementsCount' => 0,
    'totalPointsCount' => 0,
    'numEarnedSoftcoreAchievements' => 0,
    'numEarnedHardcoreAchievements' => 0,
    'numEarnedSoftcorePoints' => 0,
    'numEarnedHardcorePoints' => 0,
    'numEarnedWeightedPoints' => 0,
])

<?php
$hasHardcoreProgress = $numEarnedHardcoreAchievements > 0;
$hasSoftcoreProgress = $numEarnedSoftcoreAchievements > 0;
?>

<div>
    @if ($hasHardcoreProgress && !$hasSoftcoreProgress)
        <p>
            <span class="font-bold">{{ localized_number($numEarnedHardcoreAchievements) }}</span>
            of {{ localized_number($totalAchievementsCount) }} achievements
        </p>

        @if ($totalPointsCount > 0)
            <div class="flex gap-x-1">
                <div class="flex font-bold gap-x-1">
                    {{ localized_number($numEarnedHardcorePoints) }}
                    <x-points-weighted-container>
                        ({{ localized_number($numEarnedWeightedPoints) }})
                    </x-points-weighted-container>
                </div>

                <p>
                    of {{ localized_number($totalPointsCount) }} points
                </p>
            </div>
        @endif
    @elseif ($hasSoftcoreProgress && !$hasHardcoreProgress)
        <p>
            <span class="font-bold">{{ localized_number($numEarnedSoftcoreAchievements) }}</span>
            of {{ localized_number($totalAchievementsCount) }} softcore achievements
        </p>

        @if ($totalPointsCount > 0)
            <div class="flex gap-x-1">
                <div class="flex font-bold gap-x-1">
                    {{ localized_number($numEarnedSoftcorePoints) }}
                </div>

                <p>
                    of {{ localized_number($totalPointsCount) }} softcore points
                </p>
            </div>
        @endif
    @elseif ($hasHardcoreProgress && $hasSoftcoreProgress)
        <p>
            <span class="font-bold">{{ localized_number($numEarnedHardcoreAchievements) }}</span>
            hardcore {{ mb_strtolower(__res('achievement', $numEarnedHardcoreAchievements)) }}
        </p>

        @if ($totalPointsCount > 0)
            <div class="flex gap-x-1">
                <div class="flex font-bold gap-x-1">
                    {{ localized_number($numEarnedHardcorePoints) }}
                    <x-points-weighted-container>
                        ({{ localized_number($numEarnedWeightedPoints) }})
                    </x-points-weighted-container>
                </div>
                <p>hardcore {{ mb_strtolower(__res('point', $numEarnedHardcorePoints)) }}</p>
            </div>

            <p>
                <span class="font-bold">{{ localized_number($numEarnedSoftcoreAchievements) }}</span>
                softcore {{ mb_strtolower(__res('achievement', $numEarnedSoftcoreAchievements)) }}
            </p>

            <div class="flex gap-x-1">
                <div class="flex font-bold gap-x-1">
                    {{ localized_number($numEarnedSoftcorePoints) }}
                </div>
                <p>softcore {{ mb_strtolower(__res('point', $numEarnedSoftcorePoints)) }}</p>
            </div>
        @endif
    @endif
</div>
