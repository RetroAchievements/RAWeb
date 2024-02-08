@props([
    'completedGamesList' => [],
    'targetUsername' => '',
])

<ol class="flex flex-col gap-y-1.5">
    @foreach ($completedGamesList as $game)
        <li>
            <x-game-list-item :game="$game" :targetUsername="$targetUsername" />
        </li>
    @endforeach
</ol>
