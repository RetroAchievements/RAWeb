@props([
    'commentData', // Collection|ForumTopicComment[]
    'currentUser' => '',
    'currentUserPermissions', // legacy permissions
    'forumTopicId' => 0,
    'isHighlighted' => false,
    'isOriginalPoster' => false,
    'isUnverified' => false,
    'parsedPostContent' => '',
    'threadPostNumber' => 0,
    'isPreview' => false,
])

<?php

use App\Site\Enums\Permissions;

$metaContainerClassNames = "w-full mb-4 lg:mb-3 gap-x-2 flex justify-between";

// These values are nullable because we may be rendering a post preview.
$commentAuthor = null;
$commentAuthorDeletedDate = null;
$commentAuthorJoinDate = null;
$commentAuthorPermissions = null;

if (!$isPreview) {
    $commentId = $commentData->ID;
    $commentAuthor = e($commentData->Author);
    $commentAuthorDeletedDate = $commentData->user->Deleted;
    $commentAuthorJoinDate = $commentData->user->Created;
    $commentAuthorPermissions = $commentData->user->Permissions;
    $commentDateCreated = $commentData->DateCreated;
    $commentDateModified = $commentData->DateModified;
    $commentIsAuthorised = $commentData->Authorised;

    // FIXME: legacy permissions
    $isCurrentUserModerator = $currentUserPermissions >= Permissions::Moderator;
    $isCurrentUserAuthor = $currentUser === $commentAuthor;

    $showUnverifiedDisclaimer = !$commentIsAuthorised && ($isCurrentUserModerator || $isCurrentUserAuthor);
    $showAuthoriseTools = !$commentIsAuthorised && $isCurrentUserModerator;
    $showEditButton = ($isCurrentUserModerator || $isCurrentUserAuthor);

    // TODO: Move this conditional to the filter level and delete the @if() conditional.
    $canShowPost = $commentIsAuthorised || $showUnverifiedDisclaimer;
}
?>

@if ($isPreview || $canShowPost)
    <x-forum.post-container
        :commentId="$commentId ?? null"
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
                            :showUnverifiedDisclaimer="$showUnverifiedDisclaimer"
                            :isOriginalPoster="$isOriginalPoster"
                            :postCreatedTimestamp="$commentDateCreated"
                            :postEditedTimestamp="$commentDateModified"
                        />
                    </div>

                    <div class='flex gap-x-1 items-center lg:-mx-4 lg:pl-4 lg:w-[calc(100% + 32px)]'>
                        @if ($showAuthoriseTools)
                            <x-forum.post-moderation-tools :commentAuthor="$commentAuthor"/>
                        @endif

                        @if ($showEditButton)
                            <a href='/editpost.php?comment={{ $commentId }}' class='btn p-1 lg:text-xs'>Edit</a>
                        @endif

                        <x-forum.post-copy-comment-link-button
                            :commentId="$commentId"
                            :forumTopicId="$forumTopicId"
                            :threadPostNumber="$threadPostNumber"
                        />
                    </div>
                </div>
            @endif

            {!! $parsedPostContent !!}
        </div>
    </x-forum.post-container>
@endif
