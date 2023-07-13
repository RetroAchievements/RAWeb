@props([
    'showUnverifiedDisclaimer' => false,
    'isOriginalPoster' => false,
    'postCreatedTimestamp' => '',
    'postEditedTimestamp' => '',
])

<?php
use App\Site\Enums\UserPreference;
use Illuminate\Support\Carbon;

/** @var ?User $user */
$user = auth()->user();
$preferences = $user?->websitePrefs ?? 0;

$formatMetaTimestamp = function (string $rawDate, int $preferences = 0): string {
    if ($preferences && BitSet($preferences, UserPreference::Forum_ShowAbsoluteDates)) {
        return getNiceDate(strtotime($rawDate));
    }

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

$formattedPostTimestamp = $formatMetaTimestamp($postCreatedTimestamp, $preferences);
$formattedEditTimestamp = $postEditedTimestamp ? $formatMetaTimestamp($postEditedTimestamp, $preferences) : '';
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
    {{ $formattedPostTimestamp }}@if($formattedEditTimestamp)<span class='italic smalltext !leading-[14px]'>, <span class='hidden sm:inline'>last</span> edited {{ $formattedEditTimestamp }}</span> @endif
</p>