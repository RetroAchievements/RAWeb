<?php
use App\Platform\Enums\ValueFormat;
?>

@props([
    'leaderboard' => null, // Leaderboard
])

<div class="odd:bg-embed hover:bg-embed-highlight border border-transparent hover:border-[rgba(128,128,128,.3)] p-2">
    <div class="flex flex-col gap-y-1">
        <a
            href="{{ '/leaderboardinfo.php?i=' . $leaderboard->id }}"
            class="leading-3"
        >
            {{ $leaderboard->title }}
        </a>

        <p>{{ $leaderboard->description }}</p>

        @php
            $bestScore = $leaderboard->sortedEntries()->first();
        @endphp
        @if (!$bestScore)
            <p>No entries.</p>
        @else
            <div class="flex justify-between">
                {!! userAvatar($bestScore->user, iconSize: 16, iconClass: 'rounded-sm') !!}
                <a href="{{ '/leaderboardinfo.php?i=' . $leaderboard->id }}">
                    {{ ValueFormat::format($bestScore->score, $leaderboard->format) }}
                </a>
            </div>
        @endif
    </div>
</div>
