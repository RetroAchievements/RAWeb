<?php

use App\Models\Game;
use App\Community\Actions\AddGameToListAction;
use App\Community\Actions\RemoveGameFromListAction;
use App\Community\Enums\UserGameListType;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    public int $gameId = 0;

    public bool $isOnList = false;

    public ?string $label = null;

    #[Locked]
    public UserGameListType $listType = UserGameListType::Play;

    public function mount(): void
    {
        $user = request()->user();
        $onLists = $user ? getUserGameListsContaining($user, $this->gameId) : [];

        $this->isOnList = in_array($this->listType, $onLists);
    }

    public function toggle(): void
    {
        $game = Game::find($this->gameId);

        $action = $this->isOnList
            ? (new RemoveGameFromListAction())
            : (new AddGameToListAction());

        $action->execute(
            request()->user(),
            $game,
            $this->listType,
        );

        $command = $this->isOnList ? "removed" : "added";
        $this->isOnList = !$this->isOnList;

        $this->dispatch('flash-success', message: __("user-game-list.{$this->listType->value}.{$command}"));
    }
};
?>

<button
    class="btn"
    title="{{ __('user-game-list.' . $this->listType->value . ($this->isOnList ? '.remove' : '.add')) }}"
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
