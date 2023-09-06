@props([
    'completedGamesList' => [],
])

<ol class="flex flex-col gap-y-1.5">
    @foreach ($completedGamesList as $game)
        <x-completion-progress-page.game-list-item :game="$game" />
    @endforeach
</ol>
