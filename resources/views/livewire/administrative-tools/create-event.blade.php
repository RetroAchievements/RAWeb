<?php

use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\ComputeGameSortTitleAction;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Jobs\UpdateGameMetricsJob;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component implements HasForms {
    use InteractsWithForms;

    public ?string $title = null;
    public int $numberOfAchievements = 6;
    public int $user_id = EventAchievement::RAEVENTS_USER_ID;

    public function submit(): void
    {
        // Validate.
        $this->form->getState();

        $eventTitle = $this->title ?? 'New Event';
        $event = Game::create([
            'Title' => $eventTitle,
            'sort_title' => (new ComputeGameSortTitleAction())->execute($eventTitle),
            'Publisher' => 'RetroAchievements',
            'ConsoleID' => System::Events,
        ]);

        for ($i = 0; $i < $this->numberOfAchievements; $i++) {
            $achievement = Achievement::create([
                'Title' => "$eventTitle",
                'Description' => 'TBD',
                'MemAddr' => '0=1',
                'Flags' => AchievementFlag::OfficialCore->value,
                'GameID' => $event->id,
                'user_id' => $this->user_id,
                'BadgeName' => '00000',
                'DisplayOrder' => $i + 1,
            ]);
        }

        // update metrics and sync to game_achievement_set
        dispatch(new UpdateGameMetricsJob($event->id))->onQueue('game-metrics');

        Notification::make()
            ->success()
            ->title('Success')
            ->body('Created!')
            ->send();

        // Reset the form.
        $this->form->fill();
        $this->title = null;
        $this->numberOfAchievements = 6;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Title')
                    ->placeholder('New Event')
                    ->minLength(2)
                    ->maxLength(80)
                    ->required(),

                Forms\Components\TextInput::make('numberOfAchievements')
                    ->label('Number of achievements')
                    ->numeric()
                    ->default(6)
                    ->required(),

                Forms\Components\Select::make('user_id')
                    ->label('Username to use as author of new achievements')
                    ->options([
                        EventAchievement::RAEVENTS_USER_ID => "RAEvents",
                        EventAchievement::DEVQUEST_USER_ID => "DevQuest",
                    ])
                    ->default(EventAchievement::RAEVENTS_USER_ID)
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