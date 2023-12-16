@props([
    'gameId' => 1,
    'hashes' => null, // Collection<GameHash>
    'myUsername' => '',
])

<div class="flex flex-col gap-y-4">
    @if ($hashes->isEmpty())
        There are currently no hashes associated with this game.
    @endif

    @foreach ($hashes as $hashEntity)
        <x-manage-hashes.hash-entity
            :gameId="$gameId"
            :hashEntity="$hashEntity"
            :myUsername="$myUsername"
        />
    @endforeach
</div>
