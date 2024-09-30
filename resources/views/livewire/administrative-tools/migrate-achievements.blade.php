<?php

use App\Models\Achievement;
use App\Models\Game;
use App\Platform\Actions\MigrateAchievementIdsToDifferentGameId;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component implements HasForms {
    use InteractsWithForms;

    public array $achievementIds = [];
    public ?int $gameId = null;

    public function submit(): void
    {
        // Validate.
        $this->form->getState();

        /** @var User $user */
        $user = Auth::user();

        (new MigrateAchievementIdsToDifferentGameId())->execute(
            $this->achievementIds,
            $this->gameId,
            $user,
        );

        Notification::make()
            ->success()
            ->title('Success')
            ->body('Migrated!')
            ->send();

        // Reset the form.
        $this->form->fill();
        $this->achievementIds = [];
        $this->gameId = null;
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

                Forms\Components\Select::make('game')
                    ->label('Game to transfer achievements to')
                    ->placeholder('Select game')
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
                    ->getOptionLabelUsing(function (int $value): string {
                        $game = Game::find($value);

                        return "ID: {$game->id} - Title: {$game->title}";
                    })
                    ->statePath('gameId')
                    ->required(),
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
</div>