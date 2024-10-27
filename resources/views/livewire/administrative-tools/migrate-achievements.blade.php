<?php

use App\Models\Achievement;
use App\Models\Game;
use App\Models\User;
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

    public ?string $achievementIdsCsv = null;
    public ?int $gameId = null;

    public function submit(): void
    {
        // Validate.
        $this->form->getState();

        $allAchievementIds = [];

        // Split the CSV string by commas, trim excess whitespace, and filter out any empty values.
        $csvIds = array_filter(array_map('trim', explode(',', $this->achievementIdsCsv)));

        // Convert string IDs to integers.
        $csvIds = array_filter($csvIds, function ($id) {
            return is_numeric($id) && (int) $id > 0;
        });

        $allAchievementIds = array_unique(array_map('intval', $csvIds));

        if (empty($allAchievementIds)) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        (new MigrateAchievementIdsToDifferentGameId())->execute(
            $allAchievementIds,
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
                Forms\Components\Textarea::make('achievementIdsCsv')
                    ->label('Achievement IDs (CSV)')
                    ->placeholder("9,17,25")
                    ->helperText('Paste a comma-separated list of Achievement IDs.')
                    ->rows(2)
                    ->required()
                    ->rules(['regex:/^\d+(,\d+)*$/']),

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