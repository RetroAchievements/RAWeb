<script>
/**
 * Event handler for 'Filter by console' selection change event.
 * Updates 's' query parameter in the URL based on selected option.
 *
 * @param {Event} event - The select change event.
 */
 function handleConsoleChanged(event) {
    const newQueryParamValue = event.target.value;
    window.updateUrlParameter('s', newQueryParamValue);
}

/**
 * Event handler for 'Filter by request status' selection change event.
 * Updates 'x' query parameter in the URL based on selected option.
 *
 * @param {Event} event - The select change event.
 */
 function handleRequestStatusChanged(event) {
    const newQueryParamValue = event.target.value;
    window.updateUrlParameter('x', newQueryParamValue);
}
</script>

@props([
    'consoles' => [],
    'requestedSetsCount' => 0,
    'selectedConsoleId' => null,
    'selectedRequestStatus' => null,
])

<div>
    <p class="text-lg mb-2">{{ localized_number($requestedSetsCount) }} Requested Sets</p>

    <div class="embedded p-4 my-4 w-full">
        <p class="sr-only">Filters</p>

        <div class="grid sm:flex gap-y-4 sm:divide-x-2 divide-embed-highlight">
            <div class="grid gap-y-1 sm:pr-[40px]">
                <x-request-list.meta-console-filter
                    :consoles="$consoles"
                    :selectedConsoleId="$selectedConsoleId"
                />
            </div>

            <div class="grid gap-y-1 sm:px-8">
                <x-request-list.meta-request-status-filter 
                    :selectedRequestStatus="$selectedRequestStatus"
                />
            </div>  
        </div>      
    </div>
</div>