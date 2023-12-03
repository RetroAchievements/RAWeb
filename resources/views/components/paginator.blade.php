@props([
    'totalPages' => 1,
    'currentPage' => 1,
])

<?php
if ($totalPages < 2) {
    return;
}

$baseUrl = request()->url();
$queryParams = request()->query();

$previousPageNumber = $currentPage - 1;
$queryParams['page'] = ['number' => $previousPageNumber];
$previousPageUrl = $baseUrl . '?' . http_build_query($queryParams);

$nextPageNumber = $currentPage + 1;
$queryParams['page'] = ['number' => $nextPageNumber];
$nextPageUrl = $baseUrl . '?' . http_build_query($queryParams);

$firstPageNumber = 1;
$queryParams['page'] = ['number' => $firstPageNumber];
$firstPageUrl = $baseUrl . '?' . http_build_query($queryParams);

$lastPageNumber = $totalPages;
$queryParams['page'] = ['number' => $lastPageNumber];
$lastPageUrl = $baseUrl . '?' . http_build_query($queryParams);
?>

<script>
function handlePageSelected(event) {
    const newQueryParamValue = event.target.value;
    window.updateUrlParameter('page[number]', newQueryParamValue);
}
</script>

<div class="flex items-center gap-x-1">
    @if ($currentPage > 1)
        <a title="First" href="{{ $firstPageUrl }}">≪</a>
        <a title="Previous" href="{{ $previousPageUrl }}"><</a>
    @endif

    Page

    @if ($totalPages > 1)
        <label class="sr-only" for="pagination-dropdown">Page select</label>
        <select id="pagination-dropdown" onchange="handlePageSelected(event)">
            @for ($i = 1; $i <= $totalPages; $i++)
                <option value="{{ $i }}" {{ $i == $currentPage ? 'selected' : '' }}>
                    {{ $i }}
                </option>
            @endfor
        </select>
    @else
        <span>1</span>
    @endif

    of {{ $totalPages }}

    @if ($currentPage < $totalPages)
        <a title="Next" href="{{ $nextPageUrl }}">></a>
        <a title="Last" href="{{ $lastPageUrl }}">≫</a>
    @endif
</div>