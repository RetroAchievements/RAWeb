@props([
    'recentForumPosts' => [],
    'userPreferences' => 0,
])

<div class="component">
    <h3>Forum Activity</h3>

    <div class="flex flex-col gap-y-1">
        @foreach ($recentForumPosts as $recentForumPost)
            <x-forum.recent-post-item
                :authorUsername="$recentForumPost['Author']"
                :forumTopicTitle="$recentForumPost['ForumTopicTitle']"
                :hasDateTooltip="$recentForumPost['HasDateTooltip']"
                :href="$recentForumPost['URL']"
                :postedAt="$recentForumPost['PostedAt']"
                :summary="$recentForumPost['ShortMsg']"
                :tooltipLabel="$recentForumPost['TitleAttribute'] ?? null"
            />
        @endforeach
    </div>

    <div class="text-right">
        <a class="btn btn-link" href="/forumposthistory.php">more...</a>
    </div>
</div>
