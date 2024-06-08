<?php

use App\Models\Achievement;
use App\Models\Game;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Volt\Component;

new class extends Component implements HasForms {
    use InteractsWithForms;

    public array $foundAchievementIds = [];
    public array $gameIds = [];

    public function submit(): void
    {
        $this->foundAchievementIds = Achievement::whereIn('GameID', $this->gameIds)
            ->pluck('ID')
            ->toArray();

        $this->dispatch('open-modal', id: 'achievement-ids-results');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('games')
                    ->label('Games')
                    ->placeholder('Select games')
                    ->multiple()
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        return Game::with('system')
                            ->where('Title', 'like', "%{$search}%")
                            ->orWhere('ID', 'like', "%{$search}%")
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($game) {
                                return [$game->id => "ID: {$game->id} - Title: {$game->title} - System: {$game->system->name}"];
                            })
                            ->toArray();
                    })
                    ->getOptionLabelsUsing(function (array $values): array {
                        return Game::with('system')
                            ->whereIn('ID', $values)
                            ->get()
                            ->mapWithKeys(function ($game) {
                                return [$game->id => "ID: {$game->id} - Title: {$game->title} - System: {$game->system->name}"];
                            })
                            ->toArray();
                    })
                    ->statePath('gameIds'),
        ]);
    }
}

?>

<div>
    <form wire:submit="submit">
        <div class="flex flex-col gap-y-4">
            {{ $this->form }}

            <div class="flex w-full justify-end">
                <x-filament::button type="submit">Submit</x-filament::button>
            </div>
        </div>
    </form>

    <x-filament::modal id="achievement-ids-results">
        <x-slot name="heading">Found Achievement IDs</x-slot>

        {{ implode(', ', $this->foundAchievementIds) }}
    </x-filament::modal>
</div>
