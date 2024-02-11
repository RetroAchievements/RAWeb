@props([
    'recentForumPosts' => [],
    'userPreferences' => 0,
])

<div class="component">
    <h3>Forum Activity</h3>

    <div class="flex flex-col gap-y-1">
        @foreach ($recentForumPosts as $recentForumPost)
            <div class="embedded">
                <div class="flex justify-between items-center">
                    <div>
                        {!! userAvatar($recentForumPost['Author'], iconSize: 16) !!}
                        <span
                            class="smalldate
                            {{ $recentForumPost['HasDateTooltip'] ? 'cursor-help' : '' }}"
                            @if ($recentForumPost['TitleAttribute']) title="{{ $recentForumPost['TitleAttribute'] }}" @endif
                        >
                            {{ $recentForumPost['PostedAt'] }}
                        </span>
                    </div>

                    <a class="btn btn-link" href="{{ $recentForumPost['URL'] }}">View</a>
                </div>

                <p>in <a href="{{ $recentForumPost['URL'] }}">{{ $recentForumPost['ForumTopicTitle'] }}</a></p>

                <p class="comment text-overflow-wrap">
                    {{ html_entity_decode($recentForumPost['ShortMsg']) }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="text-right">
        <a class="btn btn-link" href="/forumposthistory.php">more...</a>
    </div>
</div>
