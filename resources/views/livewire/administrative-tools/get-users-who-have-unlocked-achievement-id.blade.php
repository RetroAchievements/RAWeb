<?php

use App\Models\Achievement;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Carbon;
use Livewire\Volt\Component;

new class extends Component implements HasForms {
    use InteractsWithForms;

    public array $achievementIds = [];
    public ?string $startAt = null;
    public ?string $endAt = null;
    public array $retrievedWinners = [];
    public string $selectedMode = 'any';

    public function submit(): void
    {
        // Validate.
        $this->form->getState();

        $this->retrievedWinners = getUnlocksInDateRange(
            $this->achievementIds,
            $this->startAt ?? '',
            $this->endAt ?? '',
            $this->selectedMode !== 'any'
        );

        $this->dispatch('open-modal', id: 'achievement-unlockers-results');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('achievements')
                    ->label('Achievements')
                    ->placeholder('Select achievements')
                    ->multiple()
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        return Achievement::with('game')
                            ->where('Title', 'like', "%{$search}%")
                            ->orWhere('ID', 'like', "%{$search}%")
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($achievement) {
                                return [$achievement->id => "ID: {$achievement->id} - Title: {$achievement->title} - Game: {$achievement->game->title}"];
                            })
                            ->toArray();
                    })
                    ->getOptionLabelsUsing(function (array $values): array {
                        return Achievement::with('game')
                            ->whereIn('ID', $values)
                            ->get()
                            ->mapWithKeys(function ($achievement) {
                                return [$achievement->id => "ID: {$achievement->id} - Title: {$achievement->title} - Game: {$achievement->game->title}"];
                            })
                            ->toArray();
                    })
                    ->statePath('achievementIds')
                    ->required(),

                Forms\Components\Grid::make()
                    ->columns(['sm' => 2, 'xl' => 1, '2xl' => 2])
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_at')
                            ->label('Start At (UTC time)')
                            ->statePath('startAt'),

                        Forms\Components\DateTimePicker::make('end_at')
                            ->label('End At (UTC time')
                            ->statePath('endAt'),
                    ]),

                Forms\Components\ToggleButtons::make('mode')
                    ->options([
                        'any' => 'Any',
                        'hardcore' => 'Hardcore only',
                    ])
                    ->grouped()
                    ->required()
                    ->statePath('selectedMode'),
        ]);
    }
}

?>

<div>
    <form wire:submit.prevent="submit">
        <div class="flex flex-col gap-y-4">
            {{ $this->form }}

            <div class="flex w-full justify-end">
                <x-filament::button type="submit">Submit</x-filament::button>
            </div>
        </div>
    </form>

    <x-filament::modal id="achievement-unlockers-results" width="6xl">
        <x-slot name="heading">Found Users With Unlocks</x-slot>

        @if (empty($this->retrievedWinners))
            <p>No users were found with unlocks.</p>
        @endif

        <div class="flex flex-col gap-8">
            @foreach($this->retrievedWinners as $key => $achievementId)
                <div class="flex flex-col gap-1">
                    @php
                        $numPlayers = count($this->retrievedWinners[$key]);
                    @endphp

                    <p>
                        Found
                        <span class="font-bold">{{ localized_number($numPlayers) }}</span>
                        @choice('player'|'players', $numPlayers)
                        who have unlocked achievement ID
                        <span class="font-bold">{{ $key }}</span>
                        in {{ $this->selectedMode }} mode.
                    </p>

                    <div class="text-sm">
                    @foreach ($this->retrievedWinners[$key] as $playerUsername)
                        {{ $playerUsername }}
                    @endforeach
                    </div>
                </div>
            @endforeach
        </div>

    </x-filament::modal>
</div>
