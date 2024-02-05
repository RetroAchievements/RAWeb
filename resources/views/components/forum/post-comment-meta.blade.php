<?php

use App\Enums\UserPreference;
use Illuminate\Support\Carbon;
?>

@props([
    'showUnverifiedDisclaimer' => false,
    'isOriginalPoster' => false,
    'postCreatedTimestamp' => '',
    'postEditedTimestamp' => '',
])

<?php
/** @var ?User $user */
$user = auth()->user();
$preferences = $user?->websitePrefs ?? 0;
$isShowAbsoluteDatesPreferenceSet = BitSet($preferences, UserPreference::Forum_ShowAbsoluteDates);

$shouldUseTimeAgoDate = function (string $rawDate): bool {
    $givenDate = Carbon::parse($rawDate);
    $now = Carbon::now();

    return $givenDate->gt($now->subHours(24));
};

$shouldUsePostedTimeAgoDate = $shouldUseTimeAgoDate($postCreatedTimestamp);
$shouldUseEditedTimeAgoDate = $shouldUseTimeAgoDate($postEditedTimestamp);

$formatMetaTimestamp = function (string $rawDate, bool $shouldUseTimeAgoDate, bool $isShowAbsoluteDatesPreferenceSet = false): string {
    if ($isShowAbsoluteDatesPreferenceSet) {
        return getNiceDate(strtotime($rawDate));
    }

    $givenDate = Carbon::parse($rawDate);
    if ($shouldUseTimeAgoDate) {
        // "5 minutes ago"
        return $givenDate->diffForHumans();
    } else {
        // "January 4 2012, 8:05am"
        return $givenDate->format('F j Y, g:ia');
    }
};

$formattedPostTimestamp = $formatMetaTimestamp($postCreatedTimestamp, $shouldUsePostedTimeAgoDate, $isShowAbsoluteDatesPreferenceSet);
$formattedEditTimestamp =
    $postEditedTimestamp
        ? $formatMetaTimestamp($postEditedTimestamp, $shouldUseEditedTimeAgoDate, $isShowAbsoluteDatesPreferenceSet)
        : '';

$formattedPostTimestampTooltip = $formatMetaTimestamp($postCreatedTimestamp, false, $isShowAbsoluteDatesPreferenceSet);
$formattedEditTimestampTooltip = $formatMetaTimestamp($postEditedTimestamp, false, $isShowAbsoluteDatesPreferenceSet);
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
    <span
        @if($shouldUsePostedTimeAgoDate && !$isShowAbsoluteDatesPreferenceSet)
            title="{{ $formattedPostTimestampTooltip }}"
            class="cursor-help"
        @endif
    >
        {{-- Keep this all on a single line so white space isn't added before the comma --}}
        {{ $formattedPostTimestamp }}@if($formattedEditTimestamp), @endif
    </span>

    @if($formattedEditTimestamp)
        <span class='italic smalltext !leading-[14px]'>
            <span class='hidden sm:inline'>last</span> edited
            <span
                @if($shouldUseEditedTimeAgoDate && !$isShowAbsoluteDatesPreferenceSet)
                    class="cursor-help"
                    title="{{ $formattedEditTimestampTooltip }}"
                @endif
            >
                {{ $formattedEditTimestamp }}
            </span>
        </span>
    @endif
</p>
