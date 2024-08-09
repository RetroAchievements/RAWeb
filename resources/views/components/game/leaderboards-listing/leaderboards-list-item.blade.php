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

        @if (!$leaderboard->topEntry)
            <p>No entries.</p>
        @else
            <div class="flex justify-between">
                {!! userAvatar($leaderboard->topEntry->user->username, iconSize: 16, iconClass: 'rounded-sm') !!}
                <a href="{{ '/leaderboardinfo.php?i=' . $leaderboard->id }}">
                    {{ ValueFormat::format($leaderboard->topEntry->score, $leaderboard->format) }}
                </a>
            </div>
        @endif
    </div>
</div>
