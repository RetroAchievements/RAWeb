@props([
    'commentData',
    'currentUser',
    'currentUserPermissions',
    'forumTopicId',
    'isHighlighted',
    'isOriginalPoster',
    'isUnverified',
    'threadPostNumber',
])

<?php
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Carbon;
use LegacyApp\Site\Enums\Permissions;

$commentId = $commentData['ID'];
$commentAuthor = e($commentData['Author']);
$commentAuthorJoinDate = $commentData['AuthorJoined'];
$commentAuthorPermissions = $commentData['AuthorPermissions'];
$commentAuthorPostCount = $commentData['AuthorPostCount'];
$commentDateCreated = $commentData['DateCreated'];
$commentDateModified = $commentData['DateModified'];
$commentIsAuthorised = $commentData['Authorised'];

$parsedPostContent = Shortcode::render(e($commentData['Payload']));

// "January 4, 2012"
$formattedUserJoinDate = Carbon::parse($commentAuthorJoinDate)->format('M j, Y');

$isCurrentUserAdmin = $currentUserPermissions >= Permissions::Admin;
$isCurrentUserAuthor = $currentUser === $commentAuthor;

$showUnverifiedDisclaimer = !$commentIsAuthorised && ($isCurrentUserAdmin || $isCurrentUserAuthor);
$showAuthoriseTools = !$commentIsAuthorised && $isCurrentUserAdmin;
$showEditButton = ($isCurrentUserAdmin || $isCurrentUserAuthor);

?>

<x-forum.post-container :commentId="$commentId" :isHighlighted="$isHighlighted">
    <x-forum.post-author-box
        :authorUserName="$commentAuthor"
        :authorJoinDate="$formattedUserJoinDate"
        :authorPermissions="$commentAuthorPermissions"
        :authorPostCount="$commentAuthorPostCount"
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