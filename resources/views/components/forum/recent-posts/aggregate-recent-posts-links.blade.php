@props([
    'count1d' => 0,
    'count7d' => 0,
    'commentId1d' => 0,
    'commentId7d' => 0,
    'forumTopicId' => 0,
])

@if ($count7d > 1)
    <div class="smalltext whitespace-nowrap">
        <div class="flex flex-col gap-y-1">
            @if ($count1d > 1)
                <a href="/viewtopic.php?t={{ $forumTopicId }}&c={{ $commentId1d }}#{{ $commentId1d }}">
                    {{ $count1d }} posts in the last 24 hours
                </a>
            @endif

            @if ($count7d > $count1d)
                <a href="/viewtopic.php?t={{ $forumTopicId }}&c={{ $commentId7d }}#{{ $commentId7d }}">
                    {{ $count7d }} posts in the last 7 days
                </a>
            @endif
        </div>
    </div>
@endif
