@props([
    'paginator' => null,
    'userPageNumber' => null,
])

<?php
use App\Support\Url\UrlBuilder;

$baseUrl = request()->url();
$queryParams = request()->query();

$previousPageNumber = $paginator->currentPage() - 1;
$queryParams['page'] = ['number' => $previousPageNumber];
$previousPageUrl = $baseUrl . '?' . UrlBuilder::prettyHttpBuildQuery($queryParams);

$nextPageNumber = $paginator->currentPage() + 1;
$queryParams['page'] = ['number' => $nextPageNumber];
$nextPageUrl = $baseUrl . '?' . UrlBuilder::prettyHttpBuildQuery($queryParams);

$queryParams['page'] = ['number' => 1];
$firstPageUrl = $baseUrl . '?' . UrlBuilder::prettyHttpBuildQuery($queryParams);

$userPageUrl = null;
if ($userPageNumber) {
    $queryParams['page'] = ['number' => $userPageNumber];
    $userPageUrl = $baseUrl . '?' . UrlBuilder::prettyHttpBuildQuery($queryParams);
}

$isHighlightedRankOnCurrentPage = $paginator->currentPage() === $userPageNumber;
?>

<script>
function handlePageChanged(event) {
    window.updateUrlParameter('page[number]', event.target.value);
}

function goToPage(pageNumber) {
    const page = parseInt(pageNumber, 10);
    if (page !== NaN && page > 0 && page <= {{ $paginator->lastPage() }}) {
        window.updateUrlParameter('page[number]', page);
    } else {
        alert('Please enter a valid page number.');
    }
}
</script>

<div class="flex flex-col sm:flex-row gap-y-4 items-center justify-between md:gap-x-4">
    @if (!$isHighlightedRankOnCurrentPage && $userPageNumber)
        <a
            class="btn transition-transform lg:active:scale-95"
            href="{{ $userPageUrl }}"
        >
            Jump to your ranking's page
        </a>
    @else
        <div></div>
    @endif

    <div class="flex gap-x-4 items-center">
        <div class="flex items-center gap-x-1">
            @if (!$paginator->onFirstPage())
                @if ($paginator->currentPage() > 2)
                    <a class="btn transition-transform lg:active:scale-95" href="{{ $firstPageUrl }}">« First</a>
                @endif

                <a class="btn transition-transform lg:active:scale-95" disabled href="{{ $previousPageUrl }}">< Previous</a>
            @endif
        </div>

        <div x-init="{}" class="text-xs items-center gap-x-2 @if ($paginator->lastPage() > 50) hidden sm:flex @endif">
            Viewing Page

            @if ($paginator->lastPage() > 50)
                <form onsubmit="goToPage(document.getElementById('page-number-input').value); return false;">
                    <label for="page-number-input" class="sr-only">page number</label>
                    <input
                        type="number"
                        value="{{ $paginator->currentPage() }}"
                        id="page-number-input"
                        name="page[number]"
                        min="1"
                        max="{{ $paginator->lastPage() }}"
                        class="text-xs"
                    >

                    <button type="submit" class="btn transition-transform lg:active:scale-95 sm:hidden w-full">Go</button>
                </form>
            @else
                <select @change="handlePageChanged">
                    @for ($i = 1; $i <= $paginator->lastPage(); $i++)
                        <option value="{{ $i }}" @if ($i == $paginator->currentPage()) selected @endif>
                            {{ $i }}
                        </option>
                    @endfor
                </select>
            @endif

            of {{ localized_number($paginator->lastPage()) }}
        </div>

        @if ($paginator->hasMorePages())
            <a class="btn transition-transform lg:active:scale-95" href="{{ $nextPageUrl }}">Next ></a>
        @endif
    </div>
</div>