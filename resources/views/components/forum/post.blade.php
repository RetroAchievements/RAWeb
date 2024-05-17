@props([
    'forumTopicComment' => null, // ForumTopicComment
    'isHighlighted' => false,
    'isOriginalPoster' => false,
    'isPreview' => false,
    'parsedPostContent' => '',
    'threadPostNumber' => 0,
])

<?php
use App\Enums\Permissions;
use Illuminate\Support\Facades\Auth;

$currentUser = Auth::user();
$metaContainerClassNames = "w-full mb-4 lg:mb-3 gap-x-2 flex justify-between";

// These values are nullable because we may be rendering a post preview.
$commentAuthor = null;
$commentAuthorDeletedDate = null;
$commentAuthorJoinDate = null;
$commentAuthorPermissions = null;

if (!$isPreview) {
    $commentAuthor = $forumTopicComment->user?->User ?? 'Deleted User';
    $commentAuthorDeletedDate = $forumTopicComment->user?->Deleted ?? null;
    $commentAuthorJoinDate = $forumTopicComment->user?->created_at ?? null;
    $commentAuthorPermissions = $forumTopicComment->user?->Permissions ?? null;
    $commentDateCreated = $forumTopicComment->DateCreated;
    $commentDateModified = $forumTopicComment->DateModified;

    $isCurrentUserAuthor = ($forumTopicComment->author_id ?? 0) === $currentUser?->id;

    $isCurrentUserModerator = $currentUser?->can('manage', App\Models\ForumTopicComment::class);
    $showAuthoriseTools = !$forumTopicComment->Authorised && $isCurrentUserModerator;
    $showEditButton = ($isCurrentUserModerator || $isCurrentUserAuthor);

    // TODO: Move this conditional to the filter level and delete the @if() conditional.
    $canShowPost = $currentUser?->can('view', $forumTopicComment) || $forumTopicComment->Authorised;
}
?>

@if ($isPreview || $canShowPost)
    <x-forum.post-container
        :commentId="$forumTopicComment?->id ?? 0"
        :isHighlighted="$isHighlighted ?? false"
        :isPreview="$isPreview ?? false"
    >
        <x-forum.post-author-box
            :authorUserName="$commentAuthor"
            :authorJoinDate="$commentAuthorJoinDate"
            :authorPermissions="$commentAuthorPermissions"
            :isAuthorDeleted="$commentAuthorDeletedDate !== null"
        />

        <div class='comment w-full lg:py-0 px-1 lg:px-6 {{ $isPreview ? "py-2" : "pt-2 pb-4" }}'>
            @if ($isPreview)
                <div class='{{ $metaContainerClassNames }}'>
                    <p class='smalltext !leading-[14px]'>Preview</p>
                </div>
            @else
                <div class='{{ $metaContainerClassNames }} {{ $showAuthoriseTools ? 'flex-col sm:flex-row items-start gap-y-2' : 'items-center' }}'>
                    <div class='flex gap-x-2 items-center'>
                        <x-forum.post-comment-meta
                            :isOriginalPoster="$isOriginalPoster"
                            :forumTopicComment="$forumTopicComment"
                        />
                    </div>

                    <div class='flex gap-x-1 items-center lg:-mx-4 lg:pl-4 lg:w-[calc(100% + 32px)]'>
                        @if ($showAuthoriseTools)
                            <x-forum.post-moderation-tools :commentAuthor="$commentAuthor" />
                        @endif

                        @can('update', $forumTopicComment)
                            <a
                                href="{{ route('forum.post.edit', ['forumTopicComment' => $forumTopicComment]) }}"
                                class='btn p-1 lg:text-xs'
                            >
                                Edit
                            </a>
                        @endcan

                        <x-forum.post-copy-comment-link-button
                            :forumTopicComment="$forumTopicComment"
                            :threadPostNumber="$threadPostNumber"
                        />
                    </div>
                </div>
            @endif

            {!! $parsedPostContent !!}
        </div>
    </x-forum.post-container>
@endif
