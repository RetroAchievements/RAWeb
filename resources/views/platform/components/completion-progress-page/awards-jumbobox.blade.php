@props([
    'playedCount' => 0,
    'unfinishedCount' => 0,
    'beatenSoftcoreCount' => 0,
    'beatenHardcoreCount' => 0,
    'completedCount' => 0,
    'masteredCount' => 0,
])

<?php
$currentQueryParams = request()->query();

if (isset($currentQueryParams['filter']['status'])) {
    unset($currentQueryParams['filter']['status']);
}

$canShowBeatenSoftcore = $beatenSoftcoreCount > 0;
$canShowBeatenHardcore = $beatenHardcoreCount > 0 || ($beatenHardcoreCount === 0 && $beatenSoftcoreCount === 0);
$canShowCompleted = $completedCount > 0;
$canShowMastered = $masteredCount > 0 || ($masteredCount === 0 && $completedCount === 0);

$playedUrl = url()->current() . '?' . http_build_query(array_merge($currentQueryParams, ['filter[status]' => 'null']));
$unfinishedUrl = url()->current() . '?' . http_build_query(array_merge($currentQueryParams, ['filter[status]' => 'unawarded']));
$beatenSoftcoreUrl = url()->current() . '?' . http_build_query(array_merge($currentQueryParams, ['filter[status]' => 'eq-beaten-softcore']));
$beatenHardcoreUrl = url()->current() . '?' . http_build_query(array_merge($currentQueryParams, ['filter[status]' => 'eq-beaten-hardcore']));
$completedUrl = url()->current() . '?' . http_build_query(array_merge($currentQueryParams, ['filter[status]' => 'eq-completed']));
$masteredUrl = url()->current() . '?' . http_build_query(array_merge($currentQueryParams, ['filter[status]' => 'eq-mastered']));
?>

<div class="bg-embed rounded px-4 py-2">
    <div class="grid grid-cols-2 sm:hidden">
        <a href="{{ $playedUrl }}">
            <span class="font-bold">{{ localized_number($playedCount) }}</span> Played
        </a>

        <a href="{{ $unfinishedUrl }}">
            <span class="font-bold">{{ localized_number($unfinishedCount )}}</span> Unfinished
        </a>
        
        @if ($canShowBeatenSoftcore)
            <a href="{{ $beatenSoftcoreUrl }}">
                <span class="font-bold">{{ localized_number($beatenSoftcoreCount) }}</span> Beaten (softcore)
            </a>
        @endif
        
        @if ($canShowBeatenHardcore)
            <a href="{{ $beatenHardcoreUrl }}">
                <span class="font-bold">{{ localized_number($beatenHardcoreCount) }}</span> Beaten
            </a>
        @endif
        
        @if ($canShowCompleted)
            <a href="{{ $completedUrl }}">
                <span class="font-bold">{{ localized_number($completedCount) }}</span> Completed
            </a>
        @endif
        
        @if ($canShowMastered)
            <a href="{{ $masteredUrl }}">
                <span class="font-bold">{{ localized_number($masteredCount) }}</span> Mastered
            </a>
        @endif
    </div>

    <div class="hidden sm:flex justify-around">
        <a href="{{ $playedUrl }}" class="flex flex-col items-center">
            <p class="text-lg">{{ localized_number($playedCount) }}</p>
            <p>Played</p>
        </a>

        <a href="{{ $unfinishedUrl }}" class="flex flex-col items-center">
            <p class="text-lg">{{ localized_number($unfinishedCount) }}</p>
            <p>Unfinished</p>
        </a>

        @if ($canShowBeatenSoftcore)
            <a href="{{ $beatenSoftcoreUrl }}" class="flex flex-col items-center" data-testid="beaten-softcore-link">
                <p class="text-lg">{{ localized_number($beatenSoftcoreCount) }}</p>
                <p class="whitespace-nowrap">Beaten (softcore)</p>
            </a>
        @endif

        @if ($canShowBeatenHardcore)
            <a href="{{ $beatenHardcoreUrl }}" class="flex flex-col items-center">
                <p class="text-lg">{{ localized_number($beatenHardcoreCount) }}</p>
                <p>Beaten</p>
            </a>
        @endif

        @if ($canShowCompleted)
            <a href="{{ $completedUrl }}" class="flex flex-col items-center" data-testid="completed-link">
                <p class="text-lg">{{ localized_number($completedCount) }}</p>
                <p>Completed</p>
            </a>
        @endif

        @if ($canShowMastered)
            <a href="{{ $masteredUrl }}" class="flex flex-col items-center">
                <p class="text-lg">{{ localized_number($masteredCount) }}</p>
                <p>Mastered</p>
            </a>
        @endif
    </div>
</div>