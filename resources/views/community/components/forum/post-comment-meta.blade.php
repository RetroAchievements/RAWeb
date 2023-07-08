@props([
    'showUnverifiedDisclaimer' => false,
    'isOriginalPoster' => false,
    'postCreatedTimestamp' => '',
    'postEditedTimestamp' => '',
])

<?php
use Illuminate\Support\Carbon;

$formatMetaTimestamp = function (string $rawDate): string {
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

$formattedPostTimestamp = $formatMetaTimestamp($postCreatedTimestamp);
$formattedEditTimestamp = $postEditedTimestamp ? $formatMetaTimestamp($postEditedTimestamp) : '';
?>

@if($showUnverifiedDisclaimer)
    <x-forum.post-title-chip
        tooltip="Not yet visible to the public. Please wait for a moderator to authorize this comment."
    >
        Unverified
    </x-forum.post-title-chip>
@endif

@if($isOriginalPoster)
    <x-forum.post-title-chip tooltip="Original poster">
        OP
    </x-forum.post-title-chip>
@endif

<p class='smalltext !leading-[14px]'>
    {{-- Keep this all on a single line so white space isn't added before the comma --}}
    <span title="{{ $postCreatedTimestamp }}" class="cursor-help">{{ $formattedPostTimestamp }}@if($formattedEditTimestamp), @endif</span>

    @if($formattedEditTimestamp)
        <span class='italic smalltext !leading-[14px]'>
            <span class='hidden sm:inline'>last</span> edited
            <span class="cursor-help" title="{{ $postEditedTimestamp }}">
                {{ $formattedEditTimestamp }}
            </span>
        </span> 
    @endif
</p>