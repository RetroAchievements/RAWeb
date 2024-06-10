@props([
    'completedGamesList' => [],
    'targetUsername' => '',
])

<ol class="flex flex-col gap-y-1.5">
    @foreach ($completedGamesList as $game)
        <li>
            <x-game-list-item
                :$game
                :$targetUsername
                :isExpandable="true"
            >
                <livewire:game.user-achievements-grid
                    :$targetUsername
                    :gameId="$game['GameID']"
                    :achievementCount="$game['MaxPossible']"
                />
            </x-game-list-item>
        </li>
    @endforeach
</ol>
