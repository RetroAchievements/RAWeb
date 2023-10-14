<script>
function handleToggleCookie() {
    const currentValue = getCookie('feature_aggregate_queries');

    let newValue = true;
    if (currentValue === 'true') {
        newValue = false;
    }

    setCookie('feature_aggregate_queries', `${newValue}`);
    showStatusSuccess(`feature_aggregate_queries cookie set to ${newValue}.`);
}
</script>

<div class="flex justify-between">
    <p>Beaten Games Player-facing UX</p>
    @hasfeature("beat")
        Enabled
    @else
        Disabled
    @endhasfeature
</div>

<div class="flex justify-between">
    <p>Aggregate Queries</p>

    <button class="btn" onClick="handleToggleCookie()">
        Toggle cookie
    </button>

    @hasfeature("aggregate_queries")
        Enabled
    @else
        Disabled
    @endhasfeature
</div>
