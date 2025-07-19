<?php

use App\Models\System;
use Carbon\CarbonInterval;
use Illuminate\Support\Carbon;
?>

@props([
    'firstWonDate' => '',
    'gameId' => 0,
    'consoleId' => 0,
    'gameTitle' => '',
    'highestAwardTimeTaken' => null,
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
$isEvent = $consoleId === System::Events;

$firstUnlockDate = Carbon::parse($firstWonDate);
$mostRecentUnlockDate = $mostRecentWonDate ? Carbon::parse($mostRecentWonDate) : null;

$timeToSiteAwardLabelPartOne = '';
$timeToSiteAwardLabelPartTwo = '';
$timeToSiteAwardLabelPartThree = '';
$mostRecentUnlockDateLabel = $mostRecentUnlockDate?->format('F j Y');
if ($highestAwardKind && !$isEvent && ($highestAwardTimeTaken || $highestAwardDate)) {
    $awardLabelMap = [
        'beaten-softcore' => 'Beaten',
        'beaten-hardcore' => 'Beaten',
        'completed' => 'Completed',
        'mastered' => 'Mastered',
    ];

    $timeToSiteAwardLabelPartOne = $awardLabelMap[$highestAwardKind];

    $timeUnits = [
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];

    if ($highestAwardTimeTaken) { // actual playtime (when available)
        $datesDiff = CarbonInterval::seconds($highestAwardTimeTaken)->cascade();
        $timeParts = [];

        // don't break hours into months/days/hours. just report total hours played
        if ($datesDiff->totalHours >= 1.0) {
            $hours = floor($datesDiff->totalHours);
            $timeParts[] = "$hours " . Str::plural($timeUnits['h'], $hours);
        }

        foreach (['i','s'] as $unit) {
            if ($datesDiff->$unit > 0) {
                $timeParts[] = "{$datesDiff->$unit} " . Str::plural($timeUnits[$unit], $datesDiff->$unit);
            }
        }

        $timeToSiteAwardLabelPartTwo = implode(', ', array_slice($timeParts, 0, 2));
    }

    if ($highestAwardDate) { // distance from first unlock to last unlock
        $highestAwardedAt = Carbon::createFromTimestampUTC($highestAwardDate);
        $datesDiff = $highestAwardedAt->diff($firstUnlockDate, true);

        // if the award took more than 36 hours of playtime to earn, and the elapsed wall time
        // is less than double the total playtime, assume the player left the emulator running
        // overnight and only report the wall time.
        if ($highestAwardTimeTaken > 36 * 60 * 60 && $datesDiff->totalSeconds < $highestAwardTimeTaken * 2.0) {
            $timeToSiteAwardLabelPartTwo = '';
            $highestAwardTimeTaken = null;
        }

        // if we're not reporting actual playtime or the wall clock time is more than 36 hours,
        // also report the wall clock time.
        if (!$highestAwardTimeTaken || $datesDiff->totalSeconds > 36 * 60 * 60) {
            $timeParts = [];
            foreach ($timeUnits as $unit => $text) {
                if ($datesDiff->$unit > 0) {
                    $timeParts[] = "{$datesDiff->$unit} " . Str::plural($text, $datesDiff->$unit);
                }
            }

            $timeToSiteAwardLabelPartThree = implode(', ', array_slice($timeParts, 0, 2));
        }
    }
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
                    @php
                        $exclusiveSoftcorePoints = max($numAwardedSoftcorePoints - $numAwardedHardcorePoints, 0);
                        $leftPoints = $numAwardedHardcorePoints >= $exclusiveSoftcorePoints ? $numAwardedHardcorePoints : $exclusiveSoftcorePoints;
                    @endphp

                    <span class="font-bold">{{ localized_number($leftPoints) }}</span>
                    of
                    <span class="font-bold">{{ localized_number($numPossiblePoints) }}</span>
                    points

                    @if ($exclusiveSoftcorePoints > 0 && $exclusiveSoftcorePoints < $numAwardedHardcorePoints)
                        (+<span class="font-bold">{{ localized_number($exclusiveSoftcorePoints) }}</span> softcore)
                    @elseif ($numAwardedHardcorePoints > 0 && $exclusiveSoftcorePoints > $numAwardedHardcorePoints)
                        (+<span class="font-bold">{{ localized_number($numAwardedHardcorePoints) }}</span> hardcore)
                    @endif
                </p>
            @endif
        </div>
    @endif

    @if (!$isEvent)
        {{-- c.progress-pmeta__root > div --}}
        <div @if ($variant === 'user-recently-played') class="flex !flex-col-reverse" @endif>
            @if ($mostRecentUnlockDateLabel)
                <p>
                    @if ($variant === 'user-recently-played')
                        <span>Last played</span>
                    @endif
                    {{ $mostRecentUnlockDateLabel }}
                </p>
            @endif

            @if ($timeToSiteAwardLabelPartOne && ($timeToSiteAwardLabelPartTwo || $timeToSiteAwardLabelPartThree))
                <p>
                    <span class="hidden md:inline lg:hidden">•</span>
                    {{ $timeToSiteAwardLabelPartOne }}
                    
                    @if ($numPossibleAchievements > 0)
                        @if ($timeToSiteAwardLabelPartTwo)
                            in
                            <span class="font-bold">{{ $timeToSiteAwardLabelPartTwo }}</span>

                            @if ($timeToSiteAwardLabelPartThree)
                                over {{ $timeToSiteAwardLabelPartThree }}
                            @endif
                        @elseif ($timeToSiteAwardLabelPartThree)
                            over
                            <span class="font-bold">{{ $timeToSiteAwardLabelPartThree }}</span>
                        @endif
                    @endif
                </p>
            @endif
        </div>
    @endif
</div>
