<?php

use App\Enums\UserPreference;
use Illuminate\Support\Carbon;
?>

@props([
    'forumTopicComment' => null, // ForumTopicComment
])

<?php
$postCreatedTimestamp = $forumTopicComment->DateCreated;
$postEditedTimestamp =
    ($forumTopicComment->DateModified
    && $forumTopicComment->DateModified != $forumTopicComment->DateCreated)
        ? $forumTopicComment->DateModified
        : null;

/** @var ?User $user */
$user = auth()->user();
$preferences = $user?->websitePrefs ?? 0;
$isShowAbsoluteDatesPreferenceSet = $user?->prefers_absolute_dates ?? false;

$shouldUseTimeAgoDate = function (?string $rawDate): bool {
    if (!$rawDate) {
        return false;
    }
    
    $givenDate = Carbon::parse($rawDate);
    $now = Carbon::now();

    return $givenDate->gt($now->subHours(24));
};

$shouldUsePostedTimeAgoDate = $shouldUseTimeAgoDate($postCreatedTimestamp);
$shouldUseEditedTimeAgoDate = $shouldUseTimeAgoDate($postEditedTimestamp);

$formatMetaTimestamp = function (?string $rawDate, bool $shouldUseTimeAgoDate, bool $isShowAbsoluteDatesPreferenceSet = false): string {
    if (!$rawDate) {
        return '';
    }
    
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
$formattedEditTimestamp = $formatMetaTimestamp($postEditedTimestamp, $shouldUseEditedTimeAgoDate, $isShowAbsoluteDatesPreferenceSet);

$formattedPostTimestampTooltip = $formatMetaTimestamp($postCreatedTimestamp, false, $isShowAbsoluteDatesPreferenceSet);
$formattedEditTimestampTooltip = $formatMetaTimestamp($postEditedTimestamp, false, $isShowAbsoluteDatesPreferenceSet);

$isOriginalPoster = $forumTopicComment->user->is($forumTopicComment->forumTopic->user);
?>

@if (!$forumTopicComment->Authorised && ($forumTopicComment->author_id === $user?->id || $user?->can('manage', App\Models\ForumTopicComment::class)))
    <x-forum.topic-comment.title-chip
        tooltip="Not yet visible to the public. Please wait for a moderator to authorize this comment."
    >
        Unverified
    </x-forum.topic-comment.title-chip>
@endif

@if ($isOriginalPoster)
    <x-forum.topic-comment.title-chip tooltip="Original poster">
        OP
    </x-forum.topic-comment.title-chip>
@endif

<p class='smalltext !leading-[14px]'>
    <span
        @if ($shouldUsePostedTimeAgoDate && !$isShowAbsoluteDatesPreferenceSet)
            title="{{ $formattedPostTimestampTooltip }}"
            class="cursor-help"
        @endif
    >
        {{-- Keep this all on a single line so white space isn't added before the comma --}}
        {{ $formattedPostTimestamp }}@if($formattedEditTimestamp), @endif
    </span>

    @if ($formattedEditTimestamp)
        <span class='italic smalltext !leading-[14px]'>
            <span class='hidden sm:inline'>last</span> edited
            <span
                @if ($shouldUseEditedTimeAgoDate && !$isShowAbsoluteDatesPreferenceSet)
                    class="cursor-help"
                    title="{{ $formattedEditTimestampTooltip }}"
                @endif
            >
                {{ $formattedEditTimestamp }}
            </span>
        </span>
    @endif
</p>
