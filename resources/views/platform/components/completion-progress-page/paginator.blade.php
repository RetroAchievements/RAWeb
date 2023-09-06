@props([
    'totalPages' => 1,
    'currentPage' => 1,
])

<?php
$currentQueryParams = request()->query();

$firstPageUrl = url()->current() . '?' . http_build_query(array_merge($currentQueryParams, ['p' => 0]));
$previousPageUrl = url()->current() . '?' . http_build_query(array_merge($currentQueryParams, ['p' => $currentPage - 1]));
$nextPageUrl = url()->current() . '?' . http_build_query(array_merge($currentQueryParams, ['p' => $currentPage + 1]));
$lastPageUrl = url()->current() . '?' . http_build_query(array_merge($currentQueryParams, ['p' => $totalPages - 1]));
?>

<script>
function handlePageSelected(event) {
    const newQueryParamValue = event.target.value;
    window.updateUrlParameter('p', newQueryParamValue);
}
</script>

<div class="flex items-center gap-x-1">
    @if ($currentPage > 0)
        <a title="First" href="{{ $firstPageUrl }}">≪</a>
        <a title="Previous" href="{{ $previousPageUrl }}"><</a>
    @endif

    Page

    @if ($totalPages > 1)
        <label class="sr-only" for="pagination-dropdown">Page select</label>
        <select id="pagination-dropdown" onchange="handlePageSelected(event)">
            @for ($i = 0; $i < $totalPages; $i++)
                <option value="{{ $i }}" {{ $i == $currentPage ? 'selected' : '' }}>
                    {{ $i+1 }}
                </option>
            @endfor
        </select>
    @else
        <span>1</span>
    @endif

    of {{ $totalPages }}

    @if ($currentPage < $totalPages - 1)
        <a title="Next" href="{{ $nextPageUrl }}">></a>
        <a title="Last" href="{{ $lastPageUrl }}">≫</a>
    @endif
</div>