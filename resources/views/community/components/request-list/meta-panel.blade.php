<script>
/**
 * Updates a query parameter in the current URL and navigates to the new URL.
 *
 * @param {string} paramName - The name of the query parameter to update.
 * @param {string} newQueryParamValue - The new value for the query parameter.
 */
 function updateUrlParameter(paramName, newQueryParamValue) {
    const url = new URL(window.location.href);
    const params = new URLSearchParams(url.search);

    params.set(paramName, newQueryParamValue);
    url.search = params.toString();

    window.location.href = url.toString();
}

/**
 * Event handler for 'Filter by console' selection change event.
 * Updates 's' query parameter in the URL based on selected option.
 *
 * @param {Event} event - The select change event.
 */
 function handleConsoleChanged(event) {
    const newQueryParamValue = event.target.value;
    updateUrlParameter('s', newQueryParamValue);
}

/**
 * Event handler for 'Filter by request status' selection change event.
 * Updates 'x' query parameter in the URL based on selected option.
 *
 * @param {Event} event - The select change event.
 */
 function handleRequestStatusChanged(event) {
    const newQueryParamValue = event.target.value;
    updateUrlParameter('x', newQueryParamValue);
}
</script>

@props([
    'consoles' => [],
    'requestedSetsCount' => 0,
    'selectedConsoleId' => null,
    'selectedRequestStatus' => null,
])

<div x-init="{}">
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