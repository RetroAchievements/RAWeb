@props([
    'consoleId' => 0,
    'firstWonDate' => '',
    'gameId' => 0,
    'gameTitle' => '',
    'highestAwardDate' => null,
    'highestAwardKind' => null, // null | 'beaten-softcore' | 'beaten-hardcore' | 'completed' | 'mastered'
    'mostRecentWonDate' => '',
    'numAwardedAchievements' => 0,
    'numAwardedHardcorePoints' => null,
    'numAwardedSoftcorePoints' => null,
    'numPossibleAchievements' => 0,
    'numPossiblePoints' => null,
    'variant' => 'user-progress', // 'user-progress' | 'user-recent-played'
])

<?php
use Illuminate\Support\Carbon;

$firstUnlockDate = Carbon::parse($firstWonDate);
$mostRecentUnlockDate = Carbon::parse($mostRecentWonDate);

$timeToSiteAwardLabelPartOne = '';
$timeToSiteAwardLabelPartTwo = '';
$mostRecentUnlockDateLabel = $mostRecentUnlockDate->format('F j Y');
if ($highestAwardKind && $highestAwardDate) {
    $highestAwardedAt = Carbon::createFromTimestamp($highestAwardDate);

    $awardLabelMap = [
        'beaten-softcore' => 'Beaten',
        'beaten-hardcore' => 'Beaten',
        'completed' => 'Completed',
        'mastered' => 'Mastered',
    ];

    $timeToSiteAwardLabelPartOne = $awardLabelMap[$highestAwardKind];

    $datesDiff = $highestAwardedAt->diff($firstUnlockDate);
    $timeUnits = [
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];

    $timeParts = [];
    foreach ($timeUnits as $unit => $text) {
        if ($datesDiff->$unit) {
            $timeParts[] = "{$datesDiff->$unit} " . ($datesDiff->$unit === 1 ? $text : $text . 's');
        }
    }

    $timeToSiteAwardLabelPartTwo .= implode(', ', array_slice($timeParts, 0, 2));
}
?>

<div class="cprogress-pmeta__root">
    {{-- c.progress-pmeta__root > a --}}
    <a
        href="{{ route('game.show', $gameId) }}"
        x-data="tooltipComponent($el, {dynamicType: 'game', dynamicId: '{{ $gameId }}'})" 
        @mouseover="showTooltip($event)"
        @mouseleave="hideTooltip"
        @mousemove="trackMouseMovement($event)"
    >
        <x-game-title :rawTitle="$gameTitle" />
    </a>

    {{-- c.progress-pmeta__root > p --}}
    @if ($numPossibleAchievements > 0)
        <div class="flex flex-col">
            <p>
                @if ((int) $numAwardedAchievements === (int) $numPossibleAchievements)
                    All <span class="font-bold">{{ $numAwardedAchievements }}</span> 
                @else
                    <span class="font-bold">{{ $numAwardedAchievements }}</span>
                    of
                    <span class="font-bold">{{ $numPossibleAchievements }}</span>
                @endif

                achievements
            </p>

            @if ($numPossiblePoints)
                <p>
                    @if ($numAwardedSoftcorePoints === $numPossiblePoints || $numAwardedHardcorePoints === $numPossiblePoints)
                        All <span class="font-bold">{{ localized_number($numPossiblePoints) }}</span>
                    @else
                        <span class="font-bold">{{ localized_number($numAwardedSoftcorePoints ?? $numAwardedHardcorePoints ?? 0) }}</span>
                        of
                        <span class="font-bold">{{ localized_number($numPossiblePoints) }}</span>
                    @endif

                    @if ($numAwardedSoftcorePoints > $numAwardedHardcorePoints) softcore @endif

                    points
                </p>
            @endif
        </div>
    @endif

    {{-- c.progress-pmeta__root > div --}}
    <div @if ($variant === 'user-recently-played' && $consoleId != 101) class="flex !flex-col-reverse" @endif>
        <p>
            @if ($variant === 'user-recently-played')
                <span>Last played</span>
            @endif
            {{ $mostRecentUnlockDateLabel }}
        </p>

        @if ($timeToSiteAwardLabelPartOne && $timeToSiteAwardLabelPartTwo)
            <p>
                <span class="hidden md:inline lg:hidden">•</span>
                {{ $timeToSiteAwardLabelPartOne }}
                
                @if ($numPossibleAchievements > 0)
                    in
                    <span class="font-bold">{{ $timeToSiteAwardLabelPartTwo }}</span>
                @endif
            </p>
        @endif
    </div>
</div>
