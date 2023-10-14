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
    @hasfeature("aggregate_queries")
        Enabled
    @else
        Disabled
    @endhasfeature
</div>
