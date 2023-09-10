@props([
    'milestone' => [],
])

<?php
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

$kindLabel = '';
if ($milestone['which'] === 'most recent') {
    $kindLabel = 'Latest';
} else {
    $formatter = new NumberFormatter(session('locale'), NumberFormatter::ORDINAL);
    $kindLabel = $formatter->format($milestone['which']);
}

if ($milestone['kind'] === 'mastered') {
    $kindLabel .= ' mastery';
} elseif ($milestone['kind'] === 'completed') {
    $kindLabel .= ' completion';
} elseif ($milestone['kind'] === 'beaten-hardcore') {
    $kindLabel .= ' game beaten';
}elseif ($milestone['kind'] === 'beaten-softcore') {
    $kindLabel .= ' game beaten*';
}

$dateLabel = Carbon::createFromTimestamp($milestone['when'])->format('M j Y');

$showRecentBeatenSoftcoreTooltip = $milestone['kind'] === 'beaten-softcore' && $milestone['which'] === 'most recent';
?>

<tr>
    <td class="w-full py-2">
        <x-game.multiline-avatar
            :gameId="$milestone['game']['GameID']"
            :gameTitle="$milestone['game']['Title']"
            :gameImageIcon="$milestone['game']['ImageIcon']"
            :consoleName="$milestone['game']['ConsoleName']"
        />
    </td>

    <td>
        <div 
            class="flex flex-col items-end whitespace-nowrap {{ $showRecentBeatenSoftcoreTooltip ? 'cursor-help' : '' }}"
            @if ($showRecentBeatenSoftcoreTooltip) title="Game was beaten on softcore mode" @endif
        >
            <p class="text-2xs">{{ $kindLabel }}</p>
            <p class="text-2xs">{{ $dateLabel }}</p>
        </div>
    </td>
</tr>
