<?php

use App\Filament\Actions\ParseIdsFromCsvAction;
use App\Models\Achievement;
use App\Models\User;
use App\Platform\Enums\AchievementType;
use App\Platform\Jobs\UnlockPlayerAchievementJob;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component implements HasForms {
    use InteractsWithForms;

    public ?string $usernamesCsv = null;
    public ?string $achievementIdsCsv = null;
    public ?string $selectedMode = null;

    public ?array $usernamesSelect = [];
    public ?array $achievementIdsSelect = [];

    public array $validUserIds = [];
    public array $validUsernames = [];
    public array $missingUsernames = [];

    public array $validAchievementIds = [];
    public array $missingAchievementIds = [];

    public $loadedAchievements = null;

    public function rendering(): void
    {
        if ($this->loadedAchievements && $this->loadedAchievements->isNotEmpty()) {
            $this->loadedAchievements->loadMissing(['game.system']);
        }
    }

    public function submit(): void
    {
        $this->form->getState();

        $usernames = [];
        if (!empty($this->usernamesSelect)) {
            $users = User::whereIn('id', $this->usernamesSelect)->get();
            $usernames = $users->pluck('username')->toArray();
            $this->missingUsernames = [];
            $this->validUserIds = $users->pluck('id')->toArray();
            $this->validUsernames = $usernames;
        } else {
            $usernames = array_filter(array_unique(array_map('trim', preg_split('/[\s,]+/', (string) $this->usernamesCsv))));
            $users = User::whereIn('username', $usernames)->get();
            $foundUsernamesLower = $users->pluck('username')->map(fn ($u) => strtolower((string) $u))->toArray();

            $this->missingUsernames = array_values(array_filter($usernames, fn ($u) => !in_array(strtolower((string) $u), $foundUsernamesLower)));
            $this->validUserIds = $users->pluck('id')->toArray();
            $this->validUsernames = $users->pluck('username')->toArray();
        }

        if (!empty($this->achievementIdsSelect)) {
            $csvIds = array_map('intval', $this->achievementIdsSelect);
            $this->loadedAchievements = Achievement::with(['game.system'])->whereIn('id', $csvIds)->get();
            $achievements = $this->loadedAchievements;
            $foundAchievementIds = $achievements->pluck('id')->toArray();

            $this->missingAchievementIds = array_values(array_diff($csvIds, $foundAchievementIds));
            $this->validAchievementIds = $foundAchievementIds;
        } else {
            $csvIds = (new ParseIdsFromCsvAction())->execute($this->achievementIdsCsv);
            $this->loadedAchievements = Achievement::with(['game.system'])->whereIn('id', $csvIds)->get();
            $achievements = $this->loadedAchievements;
            $foundAchievementIds = $achievements->pluck('id')->toArray();

            $this->missingAchievementIds = array_values(array_diff($csvIds, $foundAchievementIds));
            $this->validAchievementIds = $foundAchievementIds;
        }

        $errors = [];

        if (empty($usernames)) {
            $errors['usernamesCsv'] = 'Please enter at least one valid username.';
        } elseif (count($usernames) > 100) {
            $errors['usernamesCsv'] = 'You can only process up to 100 users at a time. Please reduce the number of users.';
        } elseif (!empty($this->missingUsernames)) {
            $errors['usernamesCsv'] = 'Invalid Users: ' . implode(', ', $this->missingUsernames);
        }

        if (empty($csvIds)) {
            $errors['achievementIdsCsv'] = 'Please enter at least one valid achievement ID.';
        } elseif (count($csvIds) > 100) {
            $errors['achievementIdsCsv'] = 'You can only process up to 100 achievements at a time. Please reduce the number of achievements.';
        } elseif (!empty($this->missingAchievementIds)) {
            $errors['achievementIdsCsv'] = 'Invalid Achievements: ' . implode(', ', $this->missingAchievementIds);
        } else {
            $setIds = Illuminate\Support\Facades\DB::table('achievement_set_achievements')
                ->whereIn('achievement_id', $achievements->pluck('id'))
                ->pluck('achievement_set_id')
                ->unique();

            $links = Illuminate\Support\Facades\DB::table('game_achievement_sets')
                ->whereIn('achievement_set_id', $setIds)
                ->get();

            $baseGameIds = collect();
            foreach ($setIds as $setId) {
                $setLinks = $links->where('achievement_set_id', $setId);
                $coreLink = $setLinks->firstWhere('type', App\Platform\Enums\AchievementSetType::Core->value);
                $bonusLink = $setLinks->firstWhere('type', App\Platform\Enums\AchievementSetType::Bonus->value);
                $challengeLink = $setLinks->firstWhere('type', App\Platform\Enums\AchievementSetType::Challenge->value);
                $specialtyLink = $setLinks->firstWhere('type', App\Platform\Enums\AchievementSetType::Specialty->value);
                $exclusiveLink = $setLinks->firstWhere('type', App\Platform\Enums\AchievementSetType::Exclusive->value);
                $willBeBonusLink = $setLinks->firstWhere('type', App\Platform\Enums\AchievementSetType::WillBeBonus->value);
                $willBeChallengeLink = $setLinks->firstWhere('type', App\Platform\Enums\AchievementSetType::WillBeChallenge->value);
                $willBeSpecialtyLink = $setLinks->firstWhere('type', App\Platform\Enums\AchievementSetType::WillBeSpecialty->value);

                // Subsets that load with a base game fall back to that game's ID.
                // Specialty and exclusive sets remain isolated by falling back to their own core link's game ID.
                $baseGameId = $bonusLink?->game_id
                    ?? $challengeLink?->game_id
                    ?? $specialtyLink?->game_id
                    ?? $willBeBonusLink?->game_id
                    ?? $willBeChallengeLink?->game_id
                    ?? $willBeSpecialtyLink?->game_id
                    ?? $coreLink?->game_id;

                if ($baseGameId) {
                    $baseGameIds->push($baseGameId);
                }
            }

            if ($baseGameIds->unique()->count() > 1) {
                $errors['achievementIdsCsv'] = 'Achievement IDs belong to different (base) games.';
            }
        }

        if (!empty($errors)) {
            throw Illuminate\Validation\ValidationException::withMessages($errors);
        }

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
        $this->usernamesSelect = [];
        $this->achievementIdsSelect = [];
        $this->selectedMode = null;
        $this->loadedAchievements = collect();

        $this->dispatch('close-modal', id: 'confirm-unlock-modal');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('users')
                ->label('Users')
                ->tabs([
                    Tab::make('Paste Users')
                        ->icon('heroicon-o-clipboard')
                        ->schema([
                            Forms\Components\Textarea::make('usernamesCsv')
                                ->label('Usernames CSV')
                                ->placeholder("User1, User2, User3 or User1 User2 User3")
                                ->helperText("Paste a comma or space-separated list of usernames.")
                                ->disabled(fn (Get $get): bool => filled($get('usernamesSelect')))
                                ->live(debounce: 200)
                                ->rows(2)
                                ->requiredWithout('usernamesSelect')
                                ->afterStateUpdated(fn (Set $set) => $set('usernamesSelect', null)),
                        ]),
                    Tab::make('Search Users')
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema([
                            Forms\Components\Select::make('usernamesSelect')
                                ->label('Usernames')
                                ->requiredWithout('usernamesCsv')
                                ->multiple()
                                ->getOptionLabelsUsing(function (array $values): array {
                                    return User::whereIn('id', $values)->pluck('username', 'id')->toArray();
                                })
                                ->searchable()
                                ->getSearchResultsUsing(function (string $search): array {
                                    return User::search($search)
                                        ->withTrashed()
                                        ->take(50)
                                        ->get()
                                        ->pluck('username', 'id')
                                        ->toArray();
                                })
                                ->disabled(fn (Get $get): bool => filled($get('usernamesCsv')))
                                ->live()
                                ->afterStateUpdated(fn (Set $set) => $set('usernamesCsv', null))
                                ->helperText('... or search and select users to add.'),
                        ]),
                ]),
                Tabs::make('achievements')
                    ->label('Achievements')
                    ->tabs([
                        Tab::make('Paste Achievements')
                            ->icon('heroicon-o-clipboard')
                            ->schema([
                                Forms\Components\Textarea::make('achievementIdsCsv')
                                    ->label('Achievement IDs CSV')
                                    ->placeholder("9, 17, 25 or 9 17 25")
                                    ->helperText("Paste a comma or space-separated list of Achievement IDs.")
                                    ->rows(2)
                                    ->disabled(fn (Get $get): bool => filled($get('achievementIdsSelect')))
                                    ->live(debounce: 200)
                                    ->requiredWithout('achievementIdsSelect')
                                    ->rules(['regex:/^\d+([\s,]+\d+)*$/'])
                                    ->afterStateUpdated(fn (Set $set) => $set('achievementIdsSelect', null)),
                            ]),
                        Tab::make('Search Achievements')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Forms\Components\Select::make('achievementIdsSelect')
                                    ->label('Achievement IDs')
                                    ->placeholder("9, 17, 25 or 9 17 25")
                                    ->helperText("Paste a comma or space-separated list of Achievement IDs.")
                                    ->multiple()
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search): array {
                                        return Achievement::with('game')
                                            ->where('title', 'like', "%{$search}%")
                                            ->orWhere('id', 'like', "%{$search}%")
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(function ($achievement) {
                                                return [$achievement->id => "ID: {$achievement->id} - Title: {$achievement->title} - Game: {$achievement->game->title}"];
                                            })
                                            ->toArray();
                                    })
                                    ->getOptionLabelsUsing(function (array $values): array {
                                        return Achievement::with('game')
                                            ->whereIn('id', $values)
                                            ->get()
                                            ->mapWithKeys(function ($achievement) {
                                                return [$achievement->id => "ID: {$achievement->id} - Title: {$achievement->title} - Game: {$achievement->game->title}"];
                                            })
                                            ->toArray();
                                    })
                                    ->disabled(fn (Get $get): bool => filled($get('achievementIdsCsv')))
                                    ->live()
                                    ->requiredWithout('achievementIdsCsv')
                                    ->afterStateUpdated(fn (Set $set) => $set('achievementIdsCsv', null))
                                    ->helperText('... or search and select achievements to add.'),
                            ]),
                    ]),
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

    <x-filament::modal id="confirm-unlock-modal" width="4xl">
        <x-slot name="heading">Confirm Unlocks</x-slot>

        @php
            $achievements = !empty($this->loadedAchievements) ? $this->loadedAchievements : collect();
            $groupedAchievements = $achievements->groupBy('game_id');
        @endphp

        <div class="mb-6 overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    You are about to award <span class="font-medium text-gray-950 dark:text-white">{{ count($this->validAchievementIds) }}</span> achievement(s) worth a total of <span class="font-medium text-gray-950 dark:text-white">{{ number_format($achievements->sum('points')) }}</span> point(s) to <span class="font-medium text-gray-950 dark:text-white">{{ count($this->validUserIds) }}</span> user(s) in <span class="font-medium text-primary-600 dark:text-primary-400 uppercase">{{ $this->selectedMode }}</span> mode. <br> 
                    This will queue <span class="font-medium text-gray-950 dark:text-white">{{ count($this->validAchievementIds) * count($this->validUserIds) }}</span> unlock job(s).
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
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="max-h-96 overflow-y-auto overflow-x-auto">
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
                                            <img src="{{ media_asset($game->image_icon_asset_path) }}" alt="Game Icon" class="w-10 h-10 rounded-sm object-contain bg-white dark:bg-gray-900 ring-1 ring-gray-950/10 dark:ring-white/20" onerror="this.style.display='none'">
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
                                <td class="fi-ta-cell px-4 py-3 w-px align-top whitespace-nowrap">
                                    <div class="w-10 h-10">
                                        <img src="{{ media_asset('Badge/' . $achievement->image_name . '.png') }}" alt="Badge" class="w-full h-full object-contain rounded-md bg-white dark:bg-gray-900 ring-1 ring-gray-950/10 dark:ring-white/20" onerror="this.style.display='none'">
                                    </div>
                                </td>
                                <td class="fi-ta-cell px-4 py-3 align-top w-full min-w-[12rem]">
                                    <div class="flex flex-col">
                                        <a href="{{ route('achievement.show', $achievement->id) }}" target="_blank" class="font-medium text-gray-950 dark:text-white hover:underline">
                                            {{ $achievement->title }}
                                        </a>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2" title="{{ $achievement->description }}">
                                            {{ $achievement->description }}
                                        </span>
                                    </div>
                                </td>
                                <td class="fi-ta-cell px-4 py-3 w-px text-right align-top whitespace-nowrap">
                                    <div class="flex flex-col items-end gap-1 min-w-max">
                                        <span class="font-mono text-sm">{{ number_format($achievement->points) }} pts</span>
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

        <div class="mt-6 flex justify-end gap-x-4">
            <x-filament::button color="gray" x-on:click="close()">Cancel</x-filament::button>
            <x-filament::button wire:click="dispatchJobs" color="info">Confirm Unlocks</x-filament::button>
        </div>
    </x-filament::modal>
</div>