@props([
    'availableConsoleIds' => [],
    'selectedConsoleId' => null,
    'selectedSortOrder' => 'unlock_date',
    'selectedStatus' => null,
])

<script>
function handleSystemChanged(event) {
    const newQueryParamValue = event.target.value;
    window.updateUrlParameter('filter[system]', newQueryParamValue);
}

function handleSortOrderChanged(event) {
    const newQueryParamValue = event.target.value;
    window.updateUrlParameter('sort', newQueryParamValue);
}

function handleStatusChanged(event) {
    const newQueryParamValue = event.target.value;
    window.updateUrlParameter('filter[status]', newQueryParamValue);
}
</script>

@if (count($availableConsoleIds) > 1)
    <div class="embedded p-4 my-4 w-full">
        <p class="sr-only">Filters and sorts</p>

        <div class="grid sm:flex gap-y-4 sm:divide-x-2 divide-embed-highlight">
            <div class="grid gap-y-1 sm:pr-4 xl:pr-8">
                <x-completion-progress-page.meta-panel.console-filter
                    :availableConsoleIds="$availableConsoleIds"
                    :selectedConsoleId="$selectedConsoleId"
                />
            </div>

            <div class="grid gap-y-1 sm:px-8 lg:px-4 xl:px-8">
                <x-completion-progress-page.meta-panel.status-filter
                    :selectedStatus="$selectedStatus"
                />
            </div>

            <div class="grid gap-y-1 sm:pl-8 lg:pl-4 xl:pl-8">
                <x-completion-progress-page.meta-panel.sort-order
                    :selectedSortOrder="$selectedSortOrder"
                />
            </div>
        </div>
    </div>
@endif