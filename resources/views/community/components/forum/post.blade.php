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
use LegacyApp\Community\Enums\UserAction;
use LegacyApp\Site\Enums\Permissions;

$commentId = $commentData['ID'];
$commentAuthor = e($commentData['Author']);
$commentAuthorPermissions = $commentData['AuthorPermissions'];
$commentAuthorJoinDate = $commentData['AuthorJoined'];
$commentDateCreated = $commentData['DateCreated'];
$commentDateModified = $commentData['DateModified'];
$commentIsAuthorised = $commentData['Authorised'];

$parsedPostContent = Shortcode::render(e($commentData['Payload']));

$postUrl = config('app.url') . "/viewtopic.php?t=$forumTopicId&c=$commentId#$commentId";

$formatPostDate = function(string $rawDate): string {
    $givenDate = Carbon::parse($rawDate);
    $now = Carbon::now();

    if ($givenDate->gt($now->subHours(24))) {
        // "5 minutes ago"
        return $givenDate->diffForHumans();
    } else {
        // "January 4 2012, 8:05am"
        return $givenDate->format('F j Y, g:ia');
    }
};

$formattedPostTimestamp = $formatPostDate($commentDateCreated);
$formattedEditTimestamp = $commentDateModified ? $formatPostDate($commentDateModified) : '';

// "January 4, 2012"
$formattedUserJoinDate = Carbon::parse($commentAuthorJoinDate)->format('M j, Y');

$isCurrentUserAdmin = $currentUserPermissions >= Permissions::Admin;
$isCurrentUserAuthor = $currentUser === $commentAuthor;

$showUnverifiedDisclaimer = !$commentIsAuthorised && ($isCurrentUserAdmin || $isCurrentUserAuthor);
$showAuthoriseTools = !$commentIsAuthorised && $isCurrentUserAdmin;
$showEditButton = ($isCurrentUserAdmin || $isCurrentUserAuthor);


?>

<div
    id="{{ $commentId }}"
    class='{{ $isHighlighted ? 'highlight' : '' }} relative w-[calc(100%+16px)] sm:w-full -mx-2 sm:mx-0 lg:flex rounded-lg mt-3 odd:bg-embed bg-embed-highlight px-1 pb-3 pt-2'
    style="word-break: break-word;"
>
    <div class='pb-2 lg:py-2 px-0.5 border-b lg:border-b-0 lg:border-r border-neutral-700'>
        <div class='flex lg:flex-col lg:text-center items-center w-full lg:w-44'>
            {!! userAvatar($commentAuthor, label: false, iconSize: 72, iconClass: 'rounded-sm') !!}
            <div class='ml-2 lg:ml-0'>
                <div class='mb-[2px] lg:mt-1'>
                    {!! userAvatar($commentAuthor, icon: false) !!}
                </div>

                @if($commentAuthorPermissions >= Permissions::JuniorDeveloper)
                    <p class='smalltext !leading-4 !text-xs lg:!text-2xs'>{{ Permissions::toString($commentAuthorPermissions) }}</p>
                @endif
                <p class='smalltext !leading-4 !text-xs lg:!text-2xs'>1,129 Posts</p>
                <p class='smalltext !leading-4 !text-xs lg:!text-2xs'>Joined {{ $formattedUserJoinDate }}</p>
            </div>
        </div>
    </div>

    <div class='comment w-full pt-2 pb-4 lg:py-0 px-1 lg:px-6'>
        <div class='w-full mb-4 lg:mb-3 gap-x-2 flex items-center justify-between'>
            <!-- This area should be a component -->
            <div class='flex gap-x-2 items-center'>
                @if($showUnverifiedDisclaimer)
                    <x-forum.post-title-chip tooltip="Not yet visible to the public. Please wait for a moderator to authorize this comment.">Unverified</x-forum.post-title-chip>
                @endif

                @if($isOriginalPoster)
                    <x-forum.post-title-chip tooltip="Original poster">OP</x-forum.post-title-chip>
                @endif

                <p class='smalltext !leading-[14px]'>
                    {{ $formattedPostTimestamp }}@if($formattedEditTimestamp)<span class='italic smalltext !leading-[14px]'>, last edited {{ $formattedEditTimestamp }}</span> @endif
                </p>
            </div>

            <div class='flex gap-x-1 items-center lg:-mx-4 lg:pl-4 lg:w-[calc(100% + 32px)]'>
                @if($showAuthoriseTools)
                    <form action='/request/user/update.php' method='post' onsubmit="return confirm('Authorise this user and all their posts?')">
                        <input type='hidden' name='property' value="{{ UserAction::UpdateForumPostPermissions }}" />
                        <input type='hidden' name='target' value="{{ $commentAuthor }}" />
                        <input type='hidden' name='value' value='1' />
                        <button class='btn p-1 lg:text-xs'>Authorise</button>
                    </form>

                    <button class='btn btn-danger p-1 lg:text-xs'>Block</button>
                @endif

                @if($showEditButton)
                    <a href='/editpost.php?comment={{ $commentId }}' class='btn p-1 lg:text-xs'>Edit</a>
                @endif

                <button
                    class='btn p-1 absolute lg:static text-xs top-1 right-1'
                    onclick='copyToClipboard("{{ $postUrl }}"); showStatusSuccess("Copied")'
                >
                    #{{ $threadPostNumber }}
                </button>
            </div>
        </div>

        {!! $parsedPostContent !!}
    </div>
</div>