<?php

use App\Models\PlayerAchievement;
use App\Platform\Jobs\UnlockPlayerAchievementJob;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component implements HasForms {
    use InteractsWithForms;

    public ?string $fromAchievementIdsCsv = null;
    public ?string $toAchievementIdsCsv = null;

    public function submit(): void
    {
        // Validate.
        $this->form->getState();

        $fromAchievementIds = $this->processCsv($this->fromAchievementIdsCsv);
        $toAchievementIds = $this->processCsv($this->toAchievementIdsCsv);

        if (empty($fromAchievementIds) || empty($toAchievementIds)) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('No IDs found in CSV')
                ->send();

            return;
        }

        $fromAchievementIdsCount = count($fromAchievementIds);
        $toAchievementIdsCount = count($toAchievementIds);

        // determine which players have earned all of the required achievements
        $existing = PlayerAchievement::query()
            ->whereIn('achievement_id', $fromAchievementIds)
            ->select([
                'user_id',
                DB::raw('count(unlocked_at) AS casual_count'),
                DB::raw('count(unlocked_hardcore_at) AS hardcore_count'),
                DB::raw('max(unlocked_at) AS unlocked_casual_at'),
                DB::raw('max(unlocked_hardcore_at) AS unlocked_hardcore_at'),
            ])
            ->groupBy('user_id')
            ->having('casual_count', '=', $fromAchievementIdsCount)
            ->get();

        $userCount = $existing->count();
        if ($userCount === 0) {
            Notification::make()
                ->danger()
                ->title('Failed')
                ->body("No users have all {$fromAchievementIdsCount} achievements.")
                ->send();

            return;
        }

        // award the target achievements, copying the unlock times and hardcore state
        $unlockerId = Auth::user()->id;
        foreach ($existing as $playerAchievement) {
            $hardcore = ($playerAchievement->hardcore_count == $fromAchievementIdsCount);
            $timestamp = Carbon::parse($hardcore ? $playerAchievement->unlocked_hardcore_at : $playerAchievement->unlocked_casual_at);
            foreach ($toAchievementIds as $toAchievementId) {
                dispatch(
                    new UnlockPlayerAchievementJob(
                        $playerAchievement->user_id,
                        (int) $toAchievementId,
                        hardcore: $hardcore,
                        timestamp: $timestamp,
                        unlockedByUserId: $unlockerId
                    )
                );
            }
        }

        Notification::make()
            ->success()
            ->title('Success')
            ->body("Awarded {$toAchievementIdsCount} " . Str::plural('achievement', $toAchievementIdsCount) .
                   " to {$userCount} " . Str::plural('user', $userCount) . '.')
            ->send();

        // Reset the form.
        $this->form->fill();
        $this->fromAchievementIdsCsv = null;
        $this->toAchievementIdsCsv = null;
    }

    private function processCsv(string $csv): array
    {
        // Split the CSV string by commas, trim excess whitespace, and filter out any empty values.
        $csvIds = array_filter(array_map('trim', explode(',', $csv)));

        // Convert string IDs to integers.
        $csvIds = array_filter($csvIds, function ($id) {
            return is_numeric($id) && (int) $id > 0;
        });

        // Eliminate duplicates.
        return array_unique(array_map('intval', $csvIds));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('fromAchievementIdsCsv')
                    ->label('From Achievement IDs (CSV)')
                    ->placeholder("9,17,25")
                    ->helperText('Paste a comma-separated list of Achievement IDs.')
                    ->required()
                    ->rules(['regex:/^\d+(,\d+)*$/']),

                Forms\Components\TextInput::make('toAchievementIdsCsv')
                    ->label('To Achievement IDs (CSV)')
                    ->placeholder("766,17,25")
                    ->helperText('Paste a comma-separated list of Achievement IDs.')
                    ->required()
                    ->rules(['regex:/^\d+(,\d+)*$/']),

                Forms\Components\Placeholder::make('Information')
                    ->content('All users who have unlocked every achievement in the "from" list will be awarded every achievement in the "to" list.'),
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