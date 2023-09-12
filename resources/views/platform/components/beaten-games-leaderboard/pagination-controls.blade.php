@props([
    'paginator' => null,
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

$queryParams['page'] = ['number' => 1];
$firstPageUrl = $baseUrl . '?' . http_build_query($queryParams);
?>

<script>
function handlePageChanged(event) {
    window.updateUrlParameter('page[number]', event.target.value);
}
</script>

<div class="flex flex-col sm:flex-row gap-y-4 items-center justify-between md:justify-end md:gap-x-4">
    <div class="flex gap-x-4 items-center">
        <div class="flex items-center gap-x-1">
            @if (!$paginator->onFirstPage())
                @if ($paginator->currentPage() > 2)
                    <a class="btn transition-transform lg:active:scale-95" href="{{ $firstPageUrl }}">« First</a>
                @endif

                <a class="btn transition-transform lg:active:scale-95" disabled href="{{ $previousPageUrl }}">< Previous</a>
            @endif
        </div>

        <div x-init="{}" class="text-xs flex items-center gap-x-2">
            Viewing Page
            <select @change="handlePageChanged">
                @for ($i = 1; $i <= $paginator->lastPage(); $i++)
                    <option value="{{ $i }}" @if ($i == $paginator->currentPage()) selected @endif>
                        {{ $i }}
                    </option>
                @endfor
            </select>
            of {{ localized_number($paginator->lastPage()) }}
        </div>

        @if ($paginator->hasMorePages())
            <a class="btn transition-transform lg:active:scale-95" href="{{ $nextPageUrl }}">Next ></a>
        @endif
    </div>
</div>