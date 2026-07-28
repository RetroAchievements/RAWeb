<?php

use App\Community\Enums\Rank;
?>

@props([
    'hardcoreRankMeta' => [],
    'casualRankMeta' => [],
    'userMassData' => [],
    'username' => '',
])

<?php
$hardcorePoints = $userMassData['TotalPoints'] ?? 0;
$casualPoints = $userMassData['TotalSoftcorePoints'] ?? 0;

$rankMode = 'hardcore';
$rankMeta = $hardcoreRankMeta;
$rankPoints = $userMassData['TotalPoints'] ?? 0;
if ($casualPoints > $hardcorePoints) {
    $rankMode = 'casual';
    $rankMeta = $casualRankMeta;
    $rankPoints = $userMassData['TotalSoftcorePoints'] ?? 0;
}
?>

<div class="flex gap-x-2">
    <p>
        @if ($userMassData['Untracked'])
            <span class="font-bold">Site Rank:</span>
            <span class="">Untracked</span>
        @else
            <span class="font-bold">
                @if ($rankMode === 'none' || $rankMode === 'hardcore')
                    Site Rank:
                @else
                    Casual Rank:
                @endif
            </span>

            <span>
                @if ($rankPoints < Rank::MIN_POINTS)
                    <span class="italic">Requires at least {{ Rank::MIN_POINTS }} points.</span>
                @elseif (!$rankMeta['rank'] || $rankMeta['numRankedUsers'] === 0)
                    <span class="italic">Will appear shortly.</span>
                @else
                    <a href="{{ '/globalRanking.php?t=2&o=' . $rankMeta['rankOffset'] . '&s=' . ($rankMode === 'casual' ? '2' : '5') }}">
                        #{{ localized_number($rankMeta['rank']) }}
                    </a>
                    of {{ localized_number($rankMeta['numRankedUsers']) }} {{ $rankMeta['rankPercentLabel'] }}
                @endif
            </span>
        @endif
    </p>
</div>
