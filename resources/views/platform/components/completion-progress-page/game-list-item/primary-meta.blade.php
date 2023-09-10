@props([
    'firstWonDate' => '',
    'gameId' => 0,
    'gameTitle' => '',
    'highestAwardDate' => null,
    'highestAwardKind' => null, // null | 'beaten-softcore' | 'beaten-hardcore' | 'completed' | 'mastered'
    'mostRecentWonDate' => '',
    'numAwarded' => 0,
    'numPossible' => 0,
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
        {!! renderGameTitle($gameTitle) !!}
    </a>

    {{-- c.progress-pmeta__root > p --}}
    @if ($numPossible > 0)
        <p>
            @if ($numAwarded === $numPossible)
                All <span class="font-bold">{{ $numAwarded }} achievements
            @else
                <span class="font-bold">{{ $numAwarded }}</span> of <span class="font-bold">{{ $numPossible }} achievements
            @endif
        </p>
    @endif

    {{-- c.progress-pmeta__root > div --}}
    <div>
        <p>{{ $mostRecentUnlockDateLabel }}</p>
        @if ($timeToSiteAwardLabelPartOne && $timeToSiteAwardLabelPartTwo)
            <p>
                <span class="hidden md:inline lg:hidden">•</span>
                {{ $timeToSiteAwardLabelPartOne }}
                
                @if ($numPossible > 0)
                    in
                    <span class="font-bold">{{ $timeToSiteAwardLabelPartTwo }}</span>
                @endif
            </p>
        @endif
    </div>
</div>
