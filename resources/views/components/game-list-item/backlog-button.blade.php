@props([
    'gameId' => 0,
    'isOnBacklog' => false,
])

<livewire:game.add-to-list-button
    :$gameId
    :isOnList="$isOnBacklog"
/>
