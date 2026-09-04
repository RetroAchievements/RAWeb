<?php

use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Platform\Jobs\UnlockPlayerAchievementJob;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component implements HasForms {
    use InteractsWithForms;

    public ?string $fromAchievementIdsCsv = null;
    public ?string $toAchievementIdsCsv = null;

    public array $fromAchievementIds = [];
    public array $toAchievementIds = [];
    public int $userCount = 0;

    public function submit(): void
    {
        // Validate.
        $this->form->getState();

        $this->fromAchievementIds = $this->processCsv($this->fromAchievementIdsCsv);
        $this->toAchievementIds = $this->processCsv($this->toAchievementIdsCsv);

        if (empty($this->fromAchievementIds) || empty($this->toAchievementIds)) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('No IDs found in CSV')
                ->send();

            return;
        }

        // determine which players have earned all of the required achievements
        $this->userCount = $this->affectedPlayersQuery()->count();
        if (!$this->userCount) {
            Notification::make()
                ->danger()
                ->title('Failed')
                ->body('No users have all ' . count($this->fromAchievementIds) . ' achievements.')
                ->send();

            return;
        }

        $this->dispatch('open-modal', id: 'confirm-copy-unlocks-modal');
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

    private function affectedPlayersQuery(): Builder
    {
        return PlayerAchievement::query()
            ->whereIn('achievement_id', $this->fromAchievementIds)
            ->select([
                'user_id',
                DB::raw('count(unlocked_at) AS casual_count'),
                DB::raw('count(unlocked_hardcore_at) AS hardcore_count'),
                DB::raw('max(unlocked_at) AS unlocked_casual_at'),
                DB::raw('max(unlocked_hardcore_at) AS unlocked_hardcore_at'),
            ])
            ->groupBy('user_id')
            ->having('casual_count', '=', count($this->fromAchievementIds));
    }

    public function dispatchJobs(): void
    {
        $unlockerId = Auth::user()->id;
        $fromAchievementIdsCount = count($this->fromAchievementIds);

        // award the target achievements, copying the unlock times and hardcore state
        $existing = $this->affectedPlayersQuery()->get();
        foreach ($existing as $playerAchievement) {
            $hardcore = ($playerAchievement->hardcore_count == $fromAchievementIdsCount);
            $timestamp = Carbon::parse($hardcore ? $playerAchievement->unlocked_hardcore_at : $playerAchievement->unlocked_casual_at);
            foreach ($this->toAchievementIds as $toAchievementId) {
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

        $userCount = $existing->count();
        $toAchievementIdsCount = count($this->toAchievementIds);

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
        $this->fromAchievementIds = [];
        $this->toAchievementIds = [];
        $this->userCount = 0;

        $this->dispatch('close-modal', id: 'confirm-copy-unlocks-modal');
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

    <x-filament::modal id="confirm-copy-unlocks-modal" width="4xl">
        <div>
            <p class="text-sm font-medium text-gray-950 dark:text-white">
                You are about to award {{ count($this->toAchievementIds) }} {{ Str::plural('achievement', count($this->toAchievementIds)) }} to {{ $this->userCount }} {{ Str::plural('user', $this->userCount) }}.
            </p>

            <p class="text-sm text-gray-600 dark:text-gray-400">
                Please double check these IDs.
            </p>

            <p class="text-sm font-medium text-gray-950 dark:text-white mt-4">From achievements:</p>
            <div class="max-h-96 overflow-y-auto overflow-x-auto">
                <table class="fi-ta-table w-full text-sm text-left">
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @php
                            $achievements = Achievement::whereIn('id', $this->fromAchievementIds)->get();
                        @endphp
                        @foreach ($achievements as $achievement)
                            <tr class="fi-ta-row hover:bg-gray-50 dark:hover:bg-white/5 transition cursor-pointer" onclick="window.open('{{ route('achievement.show', $achievement->id) }}', '_blank')">
                                <td class="fi-ta-cell px-3 py-3 w-px text-right whitespace-nowrap">
                                    <span>{{ $achievement->id }}</span>
                                </td>
                                <td class="fi-ta-cell py-3 w-px align-top whitespace-nowrap">
                                    <div class="w-10 h-10">
                                        <img src="{{ media_asset('Badge/' . $achievement->image_name . '.png') }}" alt="Badge" class="w-full h-full object-contain rounded-md bg-white dark:bg-gray-900 ring-1 ring-gray-950/10 dark:ring-white/20" onerror="this.style.display='none'">
                                    </div>
                                </td>
                                <td class="fi-ta-cell px-4 py-3 align-top w-full min-w-48">
                                    <div class="flex flex-col">
                                        <a href="{{ route('achievement.show', $achievement->id) }}" target="_blank" class="font-medium text-gray-950 dark:text-white hover:underline">
                                            {{ $achievement->title }}
                                        </a>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2" title="{{ $achievement->description }}">
                                            {{ $achievement->description }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="text-sm font-medium text-gray-950 dark:text-white mt-4">To achievements:</p>
            <div class="max-h-96 overflow-y-auto overflow-x-auto">
                <table class="fi-ta-table w-full text-sm text-left">
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @php
                            $achievements = Achievement::whereIn('id', $this->toAchievementIds)->get();
                        @endphp
                        @foreach ($achievements as $achievement)
                            <tr class="fi-ta-row hover:bg-gray-50 dark:hover:bg-white/5 transition cursor-pointer" onclick="window.open('{{ route('achievement.show', $achievement->id) }}', '_blank')">
                                <td class="fi-ta-cell px-3 py-3 w-px text-right whitespace-nowrap">
                                    <span>{{ $achievement->id }}</span>
                                </td>
                                <td class="fi-ta-cell py-3 w-px align-top whitespace-nowrap">
                                    <div class="w-10 h-10">
                                        <img src="{{ media_asset('Badge/' . $achievement->image_name . '.png') }}" alt="Badge" class="w-full h-full object-contain rounded-md bg-white dark:bg-gray-900 ring-1 ring-gray-950/10 dark:ring-white/20" onerror="this.style.display='none'">
                                    </div>
                                </td>
                                <td class="fi-ta-cell px-4 py-3 align-top w-full min-w-48">
                                    <div class="flex flex-col">
                                        <a href="{{ route('achievement.show', $achievement->id) }}" target="_blank" class="font-medium text-gray-950 dark:text-white hover:underline">
                                            {{ $achievement->title }}
                                        </a>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2" title="{{ $achievement->description }}">
                                            {{ $achievement->description }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-x-4">
            <x-filament::button color="gray" x-on:click="close()">Cancel</x-filament::button>
            <x-filament::button wire:click="dispatchJobs" color="info">Confirm Unlocks</x-filament::button>
        </div>
    </x-filament::modal>
</div>