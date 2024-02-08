@props([
    'activePlayers' => [],
])

<table class="table-highlight">
    <tbody id="active-players-tbody">
        @foreach ($activePlayers as $activePlayer)
            <x-active-players.active-players-table-row
                :activePlayer="$activePlayer"
            />
        @endforeach
    </tbody>
</table>
