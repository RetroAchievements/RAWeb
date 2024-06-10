<?php

use App\Support\Shortcode\Shortcode;

?>

@props([
    'isForSpecificUser' => false,
    'recentForumPosts' => [],
])

<div class="flex flex-col gap-y-2">
    @foreach ($recentForumPosts as $post)
        <div class="embedded">
            <div class="relative flex justify-between">
                <div class="flex flex-col gap-y-1">
                    {!! userAvatar($post['AuthorDisplayName'] ?? $post['Author'], iconSize: 16, iconClass: 'rounded-sm mr-1') !!}
                    <span class="smalldate">
                        <x-forum.post-timestamp
                            :isAbsolute="request()->user()?->prefers_absolute_dates"
                            :postedAt="$post['PostedAt']"
                        />
                    </span>
                </div>

                @if (!$isForSpecificUser)
                    <x-forum.recent-posts.aggregate-recent-posts-links
                        :count1d="$post['Count_1d']"
                        :count7d="$post['Count_7d']"
                        :commentId1d="$post['CommentID_1d']"
                        :commentId7d="$post['CommentID_7d']"
                        :forumTopicId="$post['ForumTopicID']"
                    />
                @endif
            </div>

            <div class="flex flex-col gap-y-2">
                <p class="truncate">
                    in
                    <a href="{{ route('forum.topic', ['forumTopic' => $post['ForumTopicID'], 'comment' => $post['CommentID']]) }}#{{ $post['CommentID'] }}">
                        {{ $post['ForumTopicTitle'] }}
                    </a>
                </p>

                <p class="line-clamp-3 text-xs">
                    {{ Shortcode::stripAndClamp($post['ShortMsg'], 999) }}
                </p>
            </div>
        </div>
    @endforeach
</div>
