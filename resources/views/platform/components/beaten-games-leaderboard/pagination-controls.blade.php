@props([
    'isHighlightedRankOnCurrentPage' => false,
    'paginator' => null,
    'userPageNumber' => null,
])

<?php
$baseUrl = request()->url();
$queryParams = request()->query();

$previousPageNumber = $paginator->currentPage() - 1;
$queryParams['page'] = ['number' => $previousPageNumber];
$previousPageUrl = $baseUrl . '?' . http_build_query($queryParams);

$nextPageNumber = $paginator->currentPage() + 1;
$queryParams['page'] = ['number' => $nextPageNumber];
$nextPageUrl = $baseUrl . '?' . http_build_query($queryParams);

$userPageUrl = null;
if ($userPageNumber) {
    $queryParams['page'] = ['number' => $userPageNumber];
    $userPageUrl = $baseUrl . '?' . http_build_query($queryParams);
}
?>

<div class="flex flex-col sm:flex-row gap-y-4 items-center justify-between md:justify-between md:gap-x-4">
    @if (!$isHighlightedRankOnCurrentPage && $userPageNumber)
        <a
            class="btn transition-transform lg:active:scale-95"
            href="{{ $userPageUrl }}"
        >
            Jump to your rating's page
        </a>
    @else
        <div></div>
    @endif

    <div class="flex gap-x-4 items-center">
        @if ($paginator->onFirstPage())
            <span class="btn btn-disabled pointer-events-none" disabled>« Previous</span>
        @else
            <a class="btn transition-transform lg:active:scale-95" disabled href="{{ $previousPageUrl }}">« Previous</a>
        @endif

        <p class="text-2xs">Viewing Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</p>

        @if ($paginator->hasMorePages())
            <a class="btn transition-transform lg:active:scale-95" href="{{ $nextPageUrl }}">Next »</a>
        @else
            <span class="btn btn-disabled pointer-events-none" disabled>Next »</span>
        @endif
    </div>
</div>