<?php

use App\Models\Achievement;
use App\Models\User;
use App\Platform\Enums\AchievementType;
use App\Platform\Jobs\UnlockPlayerAchievementJob;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component implements HasForms {
    use InteractsWithForms;

    public ?string $usernamesCsv = null;
    public ?string $achievementIdsCsv = null;
    public ?string $selectedMode = null;

    public array $validUserIds = [];
    public array $validUsernames = [];
    public array $missingUsernames = [];

    public array $validAchievementIds = [];
    public array $missingAchievementIds = [];

    public function submit(): void
    {
        // Validate.
        $this->form->getState();

        // Parse Users
        $usernames = array_filter(array_unique(array_map('trim', preg_split('/[\s,]+/', $this->usernamesCsv))));
        $users = User::whereIn('username', $usernames)->get();
        $foundUsernamesLower = $users->pluck('username')->map(fn($u) => strtolower((string) $u))->toArray();

        $this->missingUsernames = array_values(array_filter($usernames, fn($u) => !in_array(strtolower((string) $u), $foundUsernamesLower)));
        $this->validUserIds = $users->pluck('id')->toArray();
        $this->validUsernames = $users->pluck('username')->toArray();

        // Parse Achievements
        $csvIds = array_filter(array_unique(array_map('trim', preg_split('/[\s,]+/', $this->achievementIdsCsv))), fn($id) => is_numeric($id) && (int) $id > 0);
        $achievements = Achievement::whereIn('id', $csvIds)->get();
        $foundAchievementIds = $achievements->pluck('id')->toArray();

        $this->missingAchievementIds = array_values(array_diff($csvIds, $foundAchievementIds));
        $this->validAchievementIds = $foundAchievementIds;

        $this->dispatch('open-modal', id: 'confirm-unlock-modal');
    }

    public function dispatchJobs(): void
    {
        if (empty($this->validUserIds) || empty($this->validAchievementIds)) {
            return;
        }

        $isHardcore = $this->selectedMode === 'hardcore';
        $unlockedByUserId = Auth::id();
        $now = now();

        foreach ($this->validUserIds as $userId) {
            foreach ($this->validAchievementIds as $achievementId) {
                dispatch(new UnlockPlayerAchievementJob(
                    userId: (int) $userId,
                    achievementId: $achievementId,
                    hardcore: $isHardcore,
                    timestamp: $now,
                    unlockedByUserId: $unlockedByUserId,
                ));
            }
        }

        Notification::make()
            ->success()
            ->title('Success')
            ->body('Dispatched unlock jobs for ' . count($this->validUserIds) . ' user(s) and ' . count($this->validAchievementIds) . ' achievement(s).')
            ->send();

        // Reset the form.
        $this->form->fill();
        $this->usernamesCsv = null;
        $this->achievementIdsCsv = null;
        $this->selectedMode = null;
        
        $this->validUserIds = [];
        $this->validUsernames = [];
        $this->missingUsernames = [];
        $this->validAchievementIds = [];
        $this->missingAchievementIds = [];

        $this->dispatch('close-modal', id: 'confirm-unlock-modal');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Textarea::make('usernamesCsv')
                    ->label('Usernames')
                    ->placeholder("User1, User2, User3 or User1 User2 User3")
                    ->helperText("Paste a comma or space-separated list of usernames.")
                    ->rows(2)
                    ->required(),

                Forms\Components\Textarea::make('achievementIdsCsv')
                    ->label('Achievement IDs')
                    ->placeholder("9, 17, 25 or 9 17 25")
                    ->helperText("Paste a comma or space-separated list of Achievement IDs.")
                    ->rows(2)
                    ->required()
                    ->rules(['regex:/^\d+([\s,]+\d+)*$/']),

                Forms\Components\ToggleButtons::make('mode')
                    ->options([
                        'softcore' => 'Softcore',
                        'hardcore' => 'Hardcore',
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

    <x-filament::modal id="confirm-unlock-modal" width="2xl">
        <x-slot name="heading">Confirm Unlocks</x-slot>

        @php
            $achievements = !empty($this->validAchievementIds) ? \App\Models\Achievement::with(['game.system'])->whereIn('id', $this->validAchievementIds)->get() : collect();
            $hasUnpromoted = $achievements->contains(fn($a) => !$a->is_promoted);
            $groupedAchievements = $achievements->groupBy('game_id');
            $isInvalidHardcore = $this->selectedMode === 'hardcore' && $hasUnpromoted;
            $hasNoValidData = empty($this->validUserIds) || empty($this->validAchievementIds);
            $hasBlockingWarning = $hasNoValidData || $isInvalidHardcore;
        @endphp

        @if($hasBlockingWarning)
            <div class="mb-6 rounded-xl bg-danger-50 p-4 text-sm text-danger-600 ring-1 ring-inset ring-danger-600/20 dark:bg-danger-500/10 dark:text-danger-500 dark:ring-danger-500/20">
                <p class="font-bold flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-5 w-5 text-danger-500" />
                    @if($hasNoValidData)
                        Warning: No valid users or achievements found.
                    @else
                        Warning: Unpromoted Achievements found in achievement list
                    @endif
                </p>
                <p class="mt-2">
                    @if($hasNoValidData)
                        Please review your inputs and try again.
                    @else
                        You are attempting to unlock hardcore achievements which are currently unpromoted. Unpromoted achievements can still be awarded but only in softcore. Either change the request to softcore or postpone the request until the achievement is promoted.
                    @endif
                </p>
            </div>
        @endif

        @if(!empty($this->missingUsernames) || !empty($this->missingAchievementIds))
            <div class="mb-6 rounded-xl bg-warning-50 p-4 text-sm text-warning-600 ring-1 ring-inset ring-warning-600/20 dark:bg-warning-500/10 dark:text-warning-500 dark:ring-warning-500/20">
                <p class="font-bold flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-information-circle" class="h-5 w-5 text-warning-500" />
                    Notice: Some inputs were not found. Please double check the following entries
                </p>
                @if(!empty($this->missingUsernames))
                    <p class="mt-2"><strong>Missing Users:</strong> {{ implode(', ', $this->missingUsernames) }}</p>
                @endif
                @if(!empty($this->missingAchievementIds))
                    <p class="mt-2"><strong>Missing Achievements:</strong> {{ implode(', ', $this->missingAchievementIds) }}</p>
                @endif
            </div>
        @endif

        @if(!$hasBlockingWarning)
            <div class="mb-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    You are about to award <span class="font-medium text-gray-950 dark:text-white">{{ count($this->validAchievementIds) }}</span> achievement(s) to <span class="font-medium text-gray-950 dark:text-white">{{ count($this->validUserIds) }}</span> user(s) in <span class="font-medium text-primary-600 dark:text-primary-400 uppercase">{{ $this->selectedMode }}</span> mode.
                </p>

                <div class="mt-4">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">Target Users</span>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($this->validUsernames as $username)
                            <a href="{{ route('user.show', $username) }}" target="_blank" class="transition hover:opacity-80">
                                <x-filament::badge color="gray">{{ $username }}</x-filament::badge>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($this->validAchievementIds))
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="max-h-96 overflow-y-auto">
                    <table class="fi-ta-table w-full text-sm text-left">
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach($groupedAchievements as $gameId => $group)
                                @php
                                    $game = $group->first()->game;
                                @endphp
                                <tr class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/10 {{ $game ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-white/10 transition' : '' }}" @if($game) onclick="window.open('{{ route('game.show', $game->id) }}', '_blank')" @endif>
                                    <th colspan="3" class="px-4 py-3 text-left">
                                        <div class="flex items-center gap-3">
                                            @if($game && !empty($game->image_icon_asset_path))
                                                <img src="{{ media_asset($game->image_icon_asset_path) }}" alt="Game Icon" class="w-8 h-8 rounded-sm object-contain bg-white dark:bg-gray-900 ring-1 ring-gray-950/10 dark:ring-white/20" onerror="this.style.display='none'">
                                            @endif
                                            <div class="flex flex-col">
                                                @if($game)
                                                    <a href="{{ route('game.show', $game->id) }}" target="_blank" class="text-sm font-bold text-gray-950 dark:text-white hover:underline">{{ $game->title }}</a>
                                                @else
                                                    <span class="text-sm font-bold text-gray-950 dark:text-white">{{ 'Unknown Game' }}</span>
                                                @endif
                                                @if($game?->system)
                                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $game->system->name }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </th>
                                </tr>
                                @foreach($group as $achievement)
                                <tr class="fi-ta-row hover:bg-gray-50 dark:hover:bg-white/5 transition cursor-pointer" onclick="window.open('{{ route('achievement.show', $achievement->id) }}', '_blank')">
                                    <td class="fi-ta-cell px-4 py-3 w-16 align-top">
                                        <img src="{{ media_asset('Badge/' . $achievement->image_name . '.png') }}" alt="Badge" class="w-10 h-10 object-contain rounded-md bg-white dark:bg-gray-900 ring-1 ring-gray-950/10 dark:ring-white/20" onerror="this.style.display='none'">
                                    </td>
                                    <td class="fi-ta-cell px-4 py-3 align-top">
                                        <div class="flex flex-col">
                                            <a href="{{ route('achievement.show', $achievement->id) }}" target="_blank" class="font-medium text-gray-950 dark:text-white hover:underline">
                                                {{ $achievement->title }}
                                            </a>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2" title="{{ $achievement->description }}">
                                                {{ $achievement->description }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell px-4 py-3 text-right align-top">
                                        <div class="flex flex-col items-end gap-1">
                                            <span class="font-mono text-sm overflow-hidden whitespace-normal" style="max-width: 550px; word-wrap: break-word;">{{ number_format($achievement->points) }} pts</span>
                                            @if(!$achievement->is_promoted && $this->selectedMode === 'hardcore')
                                                <x-filament::badge size="xs" color="danger" icon="heroicon-m-exclamation-triangle">Unpromoted</x-filament::badge>
                                            @endif
                                            @if(!empty($achievement->type))
                                                @php
                                                    $typeLabel = match ($achievement->type) {
                                                        AchievementType::Missable => 'Missable',
                                                        AchievementType::Progression => 'Progression',
                                                        AchievementType::WinCondition => 'Win Condition',
                                                        default => '',
                                                    };
                                                    $typeColor = match ($achievement->type) {
                                                        AchievementType::Missable => 'warning',
                                                        AchievementType::Progression => 'info',
                                                        AchievementType::WinCondition => 'success',
                                                        default => 'gray',
                                                    };
                                                @endphp
                                                @if($typeLabel !== '')
                                                    <x-filament::badge size="xs" color="{{ $typeColor }}">{{ $typeLabel }}</x-filament::badge>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="mt-6 flex justify-end gap-x-4">
            <x-filament::button color="gray" x-on:click="close()">Cancel</x-filament::button>
            @if(!$hasBlockingWarning)
                <x-filament::button wire:click="dispatchJobs" color="info">Confirm Unlocks</x-filament::button>
            @endif
        </div>
    </x-filament::modal>
</div>