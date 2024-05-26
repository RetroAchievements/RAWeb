<?php

use App\Support\Shortcode\Shortcode;

?>

@props([
    'isForSpecificUser' => false,
    'recentForumPosts' => [],
])

<table class="table-highlight">
    <thead>
        <tr class="do-not-highlight">
            @if (!$isForSpecificUser)
                <th>Last Post By</th>
            @endif

            <th>Message</th>

            @if (!$isForSpecificUser)
                <th class="whitespace-nowrap text-right">Additional Posts</th>
            @endif
        </tr>
    </thead>

    <tbody>
        @foreach ($recentForumPosts as $post)
            <tr>
                @if (!$isForSpecificUser)
                    <td class="py-3">
                        {!! userAvatar($post['AuthorDisplayName'] ?? $post['Author'], iconSize: 24, iconClass: 'rounded-sm mr-1') !!}
                    </td>
                @endif

                <td @if ($isForSpecificUser) class="py-2" @endif>
                    <p class="flex items-center gap-x-2">
                        <a href="/viewtopic.php?t={{ $post['ForumTopicID'] }}&c={{ $post['CommentID'] }}#{{ $post['CommentID'] }}">
                            {{ $post['ForumTopicTitle'] }}
                        </a>

                        <span class="smalldate">
                            <x-forum.post-timestamp
                                :isAbsolute="request()->user()?->prefers_absolute_dates"
                                :postedAt="$post['PostedAt']"
                            />
                        </span>

                        <div class="comment text-overflow-wrap">
                            <p class="lg:line-clamp-2 xl:line-clamp-1">
                                {{ Shortcode::stripAndClamp($post['ShortMsg'], 999) }}
                            </p>
                        </div>
                    </p>
                </td>

                @if (!$isForSpecificUser)
                    <td>
                        <x-forum.recent-posts.aggregate-recent-posts-links
                            :count1d="$post['Count_1d']"
                            :count7d="$post['Count_7d']"
                            :commentId1d="$post['CommentID_1d']"
                            :commentId7d="$post['CommentID_7d']"
                            :forumTopicId="$post['ForumTopicID']"
                        />
                    </td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
