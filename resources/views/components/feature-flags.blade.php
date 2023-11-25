<?php
// EXAMPLE
// =======
// $aggregateQueriesSource = \Request::cookie('feature_aggregate_queries') !== null ? 'cookie' : 'env';
// =======
?>

<script>
function handleToggleCookie(cookieName) {
    const currentValue = getCookie(`${cookieName}`);

    let newValue = true;
    if (currentValue === 'true') {
        newValue = false;
    }

    setCookie(`${cookieName}`, `${newValue}`);
    showStatusSuccess(`${cookieName} cookie set to ${newValue}.`);
}
</script>

{{-- EXAMPLE
<div class="flex justify-between">
    <p>Aggregate Queries</p>

    <button class="btn" onClick="handleToggleCookie('feature_aggregate_queries')">
        Toggle cookie
    </button>

    @hasfeature("aggregate_queries")
        Enabled (Source: {{ $aggregateQueriesSource }})
    @else
        Disabled (Source: {{ $aggregateQueriesSource }})
    @endhasfeature
</div> --}}
