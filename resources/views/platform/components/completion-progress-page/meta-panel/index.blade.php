@props([
    'availableConsoleIds' => [],
    'selectedConsoleId' => null,
    'selectedStatus' => null,
])

<script>
function handleConsoleChanged(event) {
    const newQueryParamValue = event.target.value;
    window.updateUrlParameter('c', newQueryParamValue);
}

function handleStatusChanged(event) {
    const newQueryParamValue = event.target.value;
    window.updateUrlParameter('s', newQueryParamValue);
}
</script>

@if (count($availableConsoleIds) > 1)
    <div class="embedded p-4 my-4 w-full">
        <p class="sr-only">Filters</p>

        <div class="grid sm:flex gap-y-4 sm:divide-x-2 divide-embed-highlight">
            <div class="grid gap-y-1 sm:pr-[40px]">
                <x-completion-progress-page.meta-panel.console-filter
                    :availableConsoleIds="$availableConsoleIds"
                    :selectedConsoleId="$selectedConsoleId"
                />
            </div>

            <div class="grid gap-y-1 sm:px-8">
                <x-completion-progress-page.meta-panel.status-filter
                    :selectedStatus="$selectedStatus"
                />
            </div>
        </div>
    </div>
@endif