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

    <div class="embedded p-4 w-full grid gap-y-4">
        <div class="grid gap-y-1">
            <label for="filter-by-console-select">Filter by console:</label>
            <select id="filter-by-console-id" class="w-full" @change="handleConsoleChanged">
                @if ($selectedConsoleId === null)
                    <option selected>Only supported systems</option>
                @else 
                    <option value=''>Only supported systems</option>
                @endif

                @if ($selectedConsoleId == -1)
                    <option selected>All systems</option>
                @else
                    <option value="-1">All systems</option>
                @endif

                @foreach ($consoles as $console)
                    @if ($selectedConsoleId == $console['ID'])
                        <option selected>{{ e($console->Name) }}</option>
                    @else
                        <option value="{{ $console['ID'] }}">{{ e($console->Name) }}</option>
                    @endif
                @endforeach
            </select>
        </div>

        <div class="grid gap-y-1">
            <p>Filter by request status:</p>
            <div class="space-x-6 flex" id="filter-by-request-status">
                <div class="flex items-center gap-x-1 text-xs">
                    <input type="radio" id="all-requests" name="request-status" value="0" {{ !$selectedRequestStatus ? 'checked' : '' }} @change="handleRequestStatusChanged">
                    <label for="all-requests">All</label>
                </div>
        
                <div class="flex items-center gap-x-1 text-xs">
                    <input type="radio" id="claimed-requests" name="request-status" value="1" {{ $selectedRequestStatus == 1 ? 'checked' : '' }} @change="handleRequestStatusChanged">
                    <label for="claimed-requests">Claimed</label>
                </div>
        
                <div class="flex items-center gap-x-1 text-xs">
                    <input type="radio" id="unclaimed-requests" name="request-status" value="2" {{ $selectedRequestStatus == 2 ? 'checked' : '' }} @change="handleRequestStatusChanged">
                    <label for="unclaimed-requests">Unclaimed</label>
                </div>
            </div>
        </div>        
    </div>
</div>