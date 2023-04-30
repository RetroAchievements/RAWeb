@props([
    'commentData',
    'forumTopicId',
    'isHighlighted',
    'isOriginalPoster',
    'threadPostNumber',
])

<?php
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Carbon;
use LegacyApp\Site\Enums\Permissions;

$commentId = $commentData['ID'];
$commentAuthor = e($commentData['Author']);
$commentAuthorPermissions = $commentData['AuthorPermissions'];
$commentAuthorJoinDate = $commentData['AuthorJoined'];
$commentDateCreated = $commentData['DateCreated'];
$commentDateModified = $commentData['DateModified'];
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
$formattedEditTimestamp = '';
if ($commentDateModified) {
    $formattedEditTimestamp = $formatPostDate($commentDateModified);
}

// "January 4, 2012"
$formattedUserJoinDate = Carbon::parse($commentAuthorJoinDate)->format('M j, Y');

?>

<div
    id="{{ $commentId }}"
    class='{{ $isHighlighted ? 'highlight' : '' }} relative w-[calc(100%+16px)] sm:w-full -mx-2 sm:mx-0 lg:flex rounded-lg mt-3 even:bg-embed bg-embed-highlight px-1 pb-3 pt-2'
>
    <button
        class='btn p-1 absolute text-xs top-1 right-1'
        onclick='copyToClipboard("{{ $postUrl }}"); showStatusSuccess("Copied")'
    >
        #{{ $threadPostNumber }}
    </button>

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

    <div class='comment pt-2 pb-4 lg:py-0 px-1 lg:px-6'>
        <div class='mb-4 lg:mb-3 flex items-center gap-x-2'>
            @if($isOriginalPoster)
                <span title='Original poster' class='cursor-help px-1 text-2xs font-semibold border border-text rounded-full'>OP</span>
            @endif

            <p class='smalltext !leading-[14px]'>
                {{ $formattedPostTimestamp }}@if($formattedEditTimestamp)<span class='italic smalltext !leading-[14px]'>, last edited {{ $formattedEditTimestamp }}</span> @endif
            </p>
        </div>

        {!! $parsedPostContent !!}
    </div>
</div>