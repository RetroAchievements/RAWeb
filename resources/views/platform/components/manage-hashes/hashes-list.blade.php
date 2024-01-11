@props([
    'gameId' => 1,
    'hashes' => null, // Collection<GameHash>
    'myUsername' => '',
])

@if (!$hashes->isEmpty())
    <div class="flex flex-col gap-y-4">
        @foreach ($hashes as $hashEntity)
            <x-manage-hashes.hash-entity
                :gameId="$gameId"
                :hashEntity="$hashEntity"
                :myUsername="$myUsername"
            />
        @endforeach
    </div>
@endif
