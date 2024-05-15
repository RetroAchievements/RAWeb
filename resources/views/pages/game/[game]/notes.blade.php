<?php

use App\Models\Game;
use Illuminate\View\View;

use function Laravel\Folio\{name, middleware, render};

name('game.notes');
middleware(['auth']);

render(function (View $view, Game $game) {
    $game = $game->loadMissing('memoryNotes.user');

    return $view->with(['game' => $game]);
});

?>

<x-app-layout
    pageTitle="Code Notes - {{ $game->title }}"
    pageDescription="A list of documented memory addresses for {{ $game->title }}"
>
    <x-game.breadcrumbs
        :game="$game"
        currentPageLabel="Code Notes"
    />

    <div class="mb-6">
        <div class="mt-3 -mb-3 w-full flex gap-x-3">
            {!! gameAvatar($game->toArray(), label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
            <h1 class="mt-[10px] w-full">Code Notes</h1>
        </div>
    </div>

    <div class="flex flex-col gap-y-6">
        <x-alert>
            <x-slot name="title">Note</x-slot>
            <x-slot name="description">
                The memory addresses shown below may differ from the addresses
                used by original gaming hardware. RetroAchievements uses a standardized
                addressing scheme where system memory starts at <span class="font-mono">$00000000</span>, 
                followed by cartridge memory. This simplifies addressing across different systems.
            </x-slot>
        </x-alert>

        @if ($game->memoryNotes->isEmpty())
            <x-empty-state>
                No one has recorded any code notes yet.
            </x-empty-state>
        @else
            <livewire:memory-notes.memory-notes-list
                :game="$game"
                :memoryNotes="$game->memoryNotes"
            />
        @endif
    </div>
</x-app-layout>

<script>
window.addEventListener('beforeunload', function (event) {
    const dirtyMonitorEls = document.querySelectorAll('div.is-dirty');

    if (dirtyMonitorEls.length) {
        const confirmationMessage = 'Any unsaved changes will be lost if you navigate away.';
        (event || window.event).returnValue = confirmationMessage;
        
        return confirmationMessage;
    }
});
</script>
