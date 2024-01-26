@props([
    'averageFinishedGames' => 0,
    'hardcoreRankMeta' => [],
    'mode' => 'hardcore', // 'hardcore' | 'softcore'
    'retroRatio' => 0,
    'softcoreRankMeta' => [],
    'totalHardcoreAchievements' => 0,
    'totalSoftcoreAchievements' => 0,
    'userMassData' => [],
])

<?php
use App\Community\Enums\Rank;

$hardcorePoints = $userMassData['TotalPoints'] ?? 0;
$softcorePoints = $userMassData['TotalSoftcorePoints'] ?? 0;
$weightedPoints = $userMassData['TotalTruePoints'] ?? 0;

$hasMixedProgress = $hardcorePoints && $softcorePoints;
?>

@if ($mode === 'hardcore')
    <x-user.profile.stat-element label="Points">
        @if ($hardcorePoints === 0)
            <span class="text-muted">0</span>
        @else
            <span class="font-bold">
                {{ localized_number($hardcorePoints) }}
                <x-points-weighted-container>
                    ({{ localized_number($weightedPoints) }})
                </x-points-weighted-container>
            </span>
        @endif
    </x-user.profile.stat-element>

    <x-user.profile.stat-element label="Site rank">
        @if ($userMassData['Untracked'])
            <span class="italic text-muted">Untracked</span>
        @elseif ($hardcorePoints > 0 && !$hardcoreRankMeta['rank'])
            <span class="italic text-muted">requires {{ Rank::MIN_POINTS }} points
        @elseif ($hardcorePoints === 0 && !$hardcoreRankMeta['rank'])
            <span class="italic text-muted">none</span>
        @else
            <a href="{{ '/globalRanking.php?t=2&o=' . $hardcoreRankMeta['rankOffset'] . '&s=5' }}">
                #{{ localized_number($hardcoreRankMeta['rank']) }}
            </a>
            of {{ localized_number($hardcoreRankMeta['numRankedUsers']) }}
        @endif
    </x-user.profile.stat-element>

    <x-user.profile.stat-element label="Achievements unlocked">
        <span class="{{ $totalHardcoreAchievements > 0 ? 'font-bold' : 'text-muted' }}">
            {{ localized_number($totalHardcoreAchievements) }}
        </span>
    </x-user.profile.stat-element>

    <x-user.profile.stat-element label="RetroRatio">
        <span class="{{ $retroRatio ? 'font-bold' : 'text-muted' }}">
            {{ $retroRatio ?? "none" }}
        </span>
    </x-user.profile.stat-element>
@elseif ($mode === 'softcore')
    <x-user.profile.stat-element label="Points (softcore)">
        <span class="{{ $softcorePoints > 0 ? 'font-bold' : 'text-muted' }}">
            {{ localized_number($softcorePoints) }}
        </span>
    </x-user.profile.stat-element>

    <x-user.profile.stat-element label="Softcore rank">
        @if ($userMassData['Untracked'])
            <span class="italic text-muted">Untracked</span>
        @elseif ($softcorePoints > 0 && !$softcoreRankMeta['rank'])
            <span class="italic text-muted">requires {{ Rank::MIN_POINTS }} softcore points
        @elseif ($softcorePoints === 0 && !$softcoreRankMeta['rank'])
            <span class="italic text-muted">none</span>
        @else
            <a href="{{ '/globalRanking.php?t=2&o=' . $softcoreRankMeta['rankOffset'] . '&s=2' }}">
                #{{ localized_number($softcoreRankMeta['rank']) }}
            </a>
            of {{ localized_number($softcoreRankMeta['numRankedUsers']) }}
        @endif
    </x-user.profile.stat-element>

    <x-user.profile.stat-element label="Achievements unlocked (softcore)">
        <span class="{{ $totalSoftcoreAchievements > 0 ? 'font-bold' : 'text-muted' }}">
            {{ localized_number($totalSoftcoreAchievements) }}
        </span>
    </x-user.profile.stat-element>

    <x-user.profile.stat-element label="Started games beaten">
        <span class="{{ $hardcorePoints > 0 || $softcorePoints > 0 ? 'font-bold' : 'text-muted' }}">
            {{ $averageFinishedGames }}%
        </span>
    </x-user.profile.stat-element>
@endif
