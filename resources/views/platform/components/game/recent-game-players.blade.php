@props([
    'recentPlayerData' => [],
    'gameTitle' => '',
])

<?php
use Illuminate\Support\Carbon;

$processedRecentPlayers = [];

foreach ($recentPlayerData as $recentPlayer) {
    $date = Carbon::parse($recentPlayer['Date'])->format('d M Y, g:ia');
    $isBroken = mb_strpos($recentPlayer['Activity'], 'Unknown macro') !== false;

    $processedRecentPlayers[] = [
        'User' => $recentPlayer['User'],
        'Date' => $date,
        'IsBroken' => $isBroken,
        'Activity' => $recentPlayer['Activity'],
    ];
}
?>

<h2 class="text-h4">Recent Players</h2>

<div class="sm:hidden flex flex-col gap-y-2">
    @foreach ($processedRecentPlayers as $recentPlayer)
        <div class="flex flex-col gap-y-0.5 px-2 py-1.5 odd:bg-embed">
            <div class="w-full flex items-center justify-between">
                {!! userAvatar($recentPlayer['User'], iconClass: 'rounded-sm', iconSize: 20) !!}
                <p class="smalldate">{{ $recentPlayer['Date'] }}</p>
            </div>

            @if ($recentPlayer['IsBroken'])
                <div class="cursor-help text-xs" title="{{ $recentPlayer['Activity'] }}">
                    <span>⚠️</span>
                    <span>Playing {{ $gameTitle }}</span>
                </div>
            @else
                <p class="text-xs">{{ $recentPlayer['Activity'] }}</p>
            @endif
        </div>
    @endforeach
</div>

<table class="hidden sm:table table-highlight">
    <thead>
        <tr>
            <th>Player</th>
            <th>When</th>
            <th class="w-full">Activity</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($processedRecentPlayers as $recentPlayer)
            <tr>
                <td class="py-2.5">{!! userAvatar($recentPlayer['User'], iconClass: 'rounded-sm mr-1', iconSize: 28) !!}</td>
                <td class="whitespace-nowrap smalldate">{{ $recentPlayer['Date'] }}</td>
                <td>
                    @if ($recentPlayer['IsBroken'])
                        <div class="cursor-help" title="{{ $recentPlayer['Activity'] }}">
                            <span>⚠️</span>
                            <span>Playing {{ $gameTitle }}</span>
                        </div>
                    @else
                        {{ $recentPlayer['Activity'] }}
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
