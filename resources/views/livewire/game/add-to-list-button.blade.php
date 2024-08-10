<?php

use App\Models\Game;
use App\Community\Actions\AddGameToListAction;
use App\Community\Actions\RemoveGameFromListAction;
use App\Community\Enums\UserGameListType;

use function Livewire\Volt\{computed, mount, state};

// == props
state(['gameId' => 0]); // TODO accept a Game rather than a gameId
state(['isOnList' => false]);
state(['label']); // ?string
state(['listType' => UserGameListType::Play])->locked();

// == state

// == actions
$toggle = function() {
    // TODO authorize

    $game = Game::find($this->gameId);

    $action = $this->isOnList
        ? (new RemoveGameFromListAction())
        : (new AddGameToListAction());

    $success = $action->execute(
        request()->user(),
        $game,
        $this->listType,
    );

    $command = $this->isOnList ? "removed" : "added";
    $this->isOnList = !$this->isOnList;

    $this->dispatch('flash-success', message: __("user-game-list.{$this->listType}.{$command}"));
};

// == lifecycle
mount(function() {
    $user = request()->user();
    $onLists = $user ? getUserGameListsContaining($user, $this->gameId) : [];

    $this->isOnList = in_array($this->listType, $onLists);
});
?>

<button
    class="btn"
    title="{{ __('user-game-list.' . $listType . ($this->isOnList ? '.remove' : '.add')) }}"
    wire:click="toggle"
>
    <div class="flex items-center gap-x-1">
        @if ($this->isOnList)
            <x-fas-check class="w-[12px] h-[12px]"/>
        @else
            <x-fas-plus class="w-[12px] h-[12px]" />
        @endif

        {{ $label }}
    </div>
</button>
