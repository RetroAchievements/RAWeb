@props([
    'commentData',
    'currentUser',
    'currentUserPermissions',
    'forumTopicId',
    'isHighlighted',
    'isOriginalPoster',
    'isUnverified',
    'parsedPostContent',
    'threadPostNumber',
])

<?php
use LegacyApp\Site\Enums\Permissions;

$commentId = $commentData['ID'];
$commentAuthor = e($commentData['Author']);
$commentAuthorJoinDate = $commentData['AuthorJoined'];
$commentAuthorPermissions = $commentData['AuthorPermissions'];
$commentDateCreated = $commentData['DateCreated'];
$commentDateModified = $commentData['DateModified'];
$commentIsAuthorised = $commentData['Authorised'];

$isCurrentUserAdmin = $currentUserPermissions >= Permissions::Admin;
$isCurrentUserAuthor = $currentUser === $commentAuthor;

$showUnverifiedDisclaimer = !$commentIsAuthorised && ($isCurrentUserAdmin || $isCurrentUserAuthor);
$showAuthoriseTools = !$commentIsAuthorised && $isCurrentUserAdmin;
$showEditButton = ($isCurrentUserAdmin || $isCurrentUserAuthor);

// TODO: Move this conditional to the filter level and delete the @if() conditional.
$canShowPost = $commentIsAuthorised || $showUnverifiedDisclaimer;
?>

@if($canShowPost)
<x-forum.post-container :commentId="$commentId" :isHighlighted="$isHighlighted">
    <x-forum.post-author-box
        :authorUserName="$commentAuthor"
        :authorJoinDate="$commentAuthorJoinDate"
        :authorPermissions="$commentAuthorPermissions"
    />

    <div class='comment w-full pt-2 pb-4 lg:py-0 px-1 lg:px-6'>
        <div class='w-full mb-4 lg:mb-3 gap-x-2 flex justify-between {{ $showAuthoriseTools ? 'flex-col sm:flex-row items-start gap-y-2' : 'items-center' }}'>
            <div class='flex gap-x-2 items-center'>
                <x-forum.post-comment-meta
                    :showUnverifiedDisclaimer="$showUnverifiedDisclaimer"
                    :isOriginalPoster="$isOriginalPoster"
                    :postCreatedTimestamp="$commentDateCreated"
                    :postEditedTimestamp="$commentDateModified"
                />
            </div>

            <div class='flex gap-x-1 items-center lg:-mx-4 lg:pl-4 lg:w-[calc(100% + 32px)]'>
                @if($showAuthoriseTools)
                    <x-forum.post-moderation-tools :commentAuthor="$commentAuthor" />
                @endif

                @if($showEditButton)
                    <a href='/editpost.php?comment={{ $commentId }}' class='btn p-1 lg:text-xs'>Edit</a>
                @endif

                <x-forum.post-copy-comment-link-button
                    :commentId="$commentId"
                    :forumTopicId="$forumTopicId"
                    :threadPostNumber="$threadPostNumber"
                />
            </div>
        </div>

        {!! $parsedPostContent !!}
    </div>
</x-forum.post-container>
@endif