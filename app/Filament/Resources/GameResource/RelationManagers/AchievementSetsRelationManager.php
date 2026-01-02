<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\RelationManagers;

use App\Filament\Resources\AchievementSetResource;
use App\Models\AchievementSet;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\User;
use App\Platform\Actions\AssociateAchievementSetToGameAction;
use App\Platform\Actions\ResolveBackingGameForAchievementSetAction;
use App\Platform\Enums\AchievementSetType;
use BackedEnum;
use Filament\Actions;
use Filament\Actions\DetachAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

class AchievementSetsRelationManager extends RelationManager
{
    protected static string $relationship = 'achievementSets';
    protected static ?string $title = 'Sets';
    protected static string|BackedEnum|null $icon = 'heroicon-o-rectangle-stack';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return !$ownerRecord->is_subset_game && $user->can('manage', GameAchievementSet::class);
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->gameAchievementSets->count();

        return $count > 0 ? "{$count}" : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

            ]);
    }

    public function table(Table $table): Table
    {
        /** @var Game $game */
        $game = $this->getOwnerRecord();

        $attachedAchievementSetIds = $game->achievementSets()
            ->wherePivot('type', '!=', AchievementSetType::Core->value)
            ->pluck('achievement_sets.id');

        /** @var User $user */
        $user = Auth::user();

        return $table
            ->recordTitle(fn (AchievementSet $record): string => "{$record->games()->first()->title}")
            ->columns([
                Tables\Columns\TextColumn::make('pivot.title')
                    ->label('Title')
                    ->placeholder(fn ($record) => $record->type === AchievementSetType::Core->value ? 'Base Set' : null),

                Tables\Columns\TextColumn::make('type')
                    ->formatStateUsing(fn ($state): ?string => AchievementSetType::tryFrom($state)?->label())
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if ($state === AchievementSetType::WillBeBonus->value) {
                            return 'Will be Bonus when multiset is enabled';
                        } elseif ($state === AchievementSetType::WillBeSpecialty->value) {
                            return 'Will be Specialty when multiset is enabled';
                        }

                        return null;
                    }),

                Tables\Columns\TextColumn::make('achievements_published')
                    ->label('Promoted Achievements'),

                Tables\Columns\TextColumn::make('achievements_unpublished')
                    ->label('Unpromoted Achievements'),

                Tables\Columns\TextInputColumn::make('order_column')
                    ->label('Display Order')
                    ->rules([
                        'required',
                        'integer',
                        'min:1',
                    ])
                    ->updateStateUsing(function ($record, $state) {
                        // Filament is going to automatically try to use the updated_at
                        // column name of the parent resource, which is `Updated`. The mismatch
                        // between parent (`Updated`) and child (`updated_at`) throws a SQL error
                        // unless we intervene.
                        $record->games()->updateExistingPivot(
                            $this->getOwnerRecord()->id,
                            [
                                'order_column' => $state,
                                'updated_at' => now(),
                            ]
                        );

                        return $state;
                    })
                    ->disabled(fn ($record) => $record->type === AchievementSetType::Core->value
                        || !$user->can('update', GameAchievementSet::class)
                    ),
            ])
            ->filters([

            ])
            ->headerActions([
                Actions\Action::make('toggleMultiset')
                    ->visible(fn () => $user->can('toggleMultiset', GameAchievementSet::class)
                        && $game->gameAchievementSets()->whereIn('type', [
                            AchievementSetType::Bonus->value,
                            AchievementSetType::WillBeBonus->value,
                            AchievementSetType::Specialty->value,
                            AchievementSetType::WillBeSpecialty->value,
                        ])->exists())
                    ->label(fn () => $this->hasWillBeTypes($game) ? 'Enable Multiset' : 'Disable Multiset')
                    ->icon(fn () => $this->hasWillBeTypes($game) ? 'heroicon-o-play' : null)
                    ->color(fn () => $this->hasWillBeTypes($game) ? 'primary' : 'danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn () => $this->hasWillBeTypes($game) ? 'Enable Multiset' : 'Disable Multiset')
                    ->modalDescription(fn () => new HtmlString($this->hasWillBeTypes($game)
                        ? 'This will allow multiple sets to be loaded at the same time in emulators that support the multiset feature.<br><br>Make absolutely sure the subset types are correct. If there are any incompatible hashes for the subsets, make sure those are configured correctly. Misconfiguration may result in a lot of tickets. If you need help, please reach out to a member of Developer Compliance, Quality Assurance, or RAdmin.<br><br>Are you sure you want to proceed?'
                        : 'This will make it only possible to load one achievement set at a time for the game. Any current players of the game are very likely to be affected by disabling multiset.<br><br>Are you sure you want to proceed?'))
                    ->action(fn () => $this->toggleMultisetTypes($game)),

                Actions\Action::make('attachSubset')
                    ->visible(fn () => $user->can('create', GameAchievementSet::class))
                    ->label('Attach Subset')
                    ->modalHeading('Attach Subset')
                    ->steps([
                        Step::make('Subset')
                            ->description('Find the subset in the database')
                            ->schema([
                                Forms\Components\Select::make('game')
                                    ->label('Subset game')
                                    ->helperText('Find the current subset game in the database. For example: "Mega Man 2 [Subset - Bonus]".')
                                    ->searchable()
                                    ->options(
                                        Game::where('title', 'like', "%[Subset -%")
                                            ->where('system_id', $game->system_id)
                                            ->where(function ($query) {
                                                $query->where('achievements_published', '>', 0)
                                                    ->orWhere('achievements_unpublished', '>', 0);
                                            })
                                            ->whereDoesntHave('gameAchievementSets', function (Builder $query) use ($attachedAchievementSetIds) {
                                                /** @var Builder<GameAchievementSet> $query */
                                                $query->core()->whereIn('achievement_set_id', $attachedAchievementSetIds);
                                            })
                                            ->orderBy('title')
                                            ->with('system')
                                            ->get()
                                            ->mapWithKeys(function ($game) {
                                                return [$game->id => "[{$game->id}] {$game->title} ({$game->system->name})"];
                                            })
                                            ->toArray()
                                    )
                                    ->required(),
                            ])
                            ->afterValidation(function (Get $get, Set $set) {
                                // Try to set the default subset title based on the selected game.
                                $gameId = (int) $get('game');
                                $game = Game::find($gameId);

                                // Extract the default subset title, ie: the part after "[Subset - " and before "]".
                                $defaultSubsetTitle = '';
                                if (preg_match('/\[Subset - (.+?)\]/', $game->title, $matches)) {
                                    $defaultSubsetTitle = $matches[1];
                                }

                                $set('title', $defaultSubsetTitle);

                                // Check if the achievement set is already attached to another game as a non-core type.
                                $legacySubsetAchievementSet = $game->gameAchievementSets()->core()->first()->achievementSet;
                                $attachedGame = $legacySubsetAchievementSet->games()
                                    ->wherePivot('type', '!=', AchievementSetType::Core->value)
                                    ->where(DB::raw('games.id'), '!=', $game->id)
                                    ->first();

                                // Set the flag in the form data.
                                $set('attachedToGameId', $attachedGame?->id);
                            }),

                        Step::make('Details')
                            ->description('Give some required info and submit')
                            ->schema([
                                Forms\Components\Hidden::make('attachedToGameId'),

                                Forms\Components\Placeholder::make('warning')
                                    ->label('')
                                    ->content(fn ($get) => new HtmlString('<p style="font-weight: bold;">🔴 This subset is already attached to another game (ID: ' . $get('attachedToGameId') . ') as a non-core set. Proceed with caution.</p>'))
                                    ->visible(fn ($get) => $get('attachedToGameId')),

                                Forms\Components\TextInput::make('title')
                                    ->minLength(2)
                                    ->maxLength(80)
                                    ->helperText('The name of the subset, such as "Bonus" or "Professor Oak Challenge". Don\'t include the word "Subset".')
                                    ->required()
                                    ->rules([
                                        'not_regex:/\[.*?\]/', // No square brackets.
                                        'not_regex:/Subset/i', // Can't include the word "Subset".
                                        'not_regex:/[\x{1F600}-\x{1F64F}]/u',  // No emojis.
                                    ]),

                                Forms\Components\Select::make('type')
                                    ->options([
                                        AchievementSetType::WillBeBonus->value => AchievementSetType::Bonus->label(),
                                        AchievementSetType::WillBeSpecialty->value => AchievementSetType::Specialty->label(),
                                        AchievementSetType::Exclusive->value => AchievementSetType::Exclusive->label(),
                                    ])
                                    ->helperText("
                                        Bonus loads with any hashes supported by Core.
                                        Specialty requires a unique hash, but also loads Core and Bonus.
                                        Exclusive requires a unique hash, but does not load Core or Bonus.
                                        When in doubt, please ask for help.
                                    ")
                                    ->required(),
                            ]),
                    ])
                    ->action(function (array $data) use ($game) {
                        try {
                            (new AssociateAchievementSetToGameAction())->execute(
                                targetGame: $game,
                                sourceGame: Game::find((int) $data['game']),
                                type: AchievementSetType::from($data['type']),
                                title: $data['title']
                            );

                            Notification::make()
                                ->success()
                                ->title('Success')
                                ->body('Subset successfully associated with the game.')
                                ->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-s-pencil')
                    ->modalHeading('Edit Achievement Set')
                    ->modalDescription('Changes to type will affect how the achievement set loads and behaves.')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Title')
                            ->minLength(2)
                            ->maxLength(80)
                            ->required()
                            ->rules([
                                function ($attribute, $value, $fail) {
                                    if ($value === null) {
                                        return;
                                    }

                                    $normalized = strtolower(trim($value));
                                    if ($normalized === 'base' || $normalized === 'base set') {
                                        $fail('The title cannot be "Base" or "Base Set".');
                                    }
                                },
                            ]),

                        Forms\Components\Select::make('type')
                            ->label('Set Type')
                            ->options([
                                AchievementSetType::WillBeBonus->value => AchievementSetType::Bonus->label(),
                                AchievementSetType::WillBeSpecialty->value => AchievementSetType::Specialty->label(),
                                AchievementSetType::Exclusive->value => AchievementSetType::Exclusive->label(),
                            ])
                            ->required()
                            ->helperText('Bonus loads with any hashes supported by Core. Specialty requires a unique hash, but also loads Core and Bonus. Exclusive requires a unique hash, but does not load Core or Bonus.'),
                    ])
                    ->fillForm(function (AchievementSet $record): array {
                        $currentType = $record->pivot->type;
                        $typeMapping = [
                            AchievementSetType::Bonus->value => AchievementSetType::WillBeBonus->value,
                            AchievementSetType::Specialty->value => AchievementSetType::WillBeSpecialty->value,
                            AchievementSetType::Exclusive->value => AchievementSetType::Exclusive->value,
                        ];

                        return [
                            'title' => $record->pivot->title,
                            'type' => $typeMapping[$currentType] ?? $currentType,
                        ];
                    })
                    ->action(function (AchievementSet $record, array $data): void {
                        $record->games()->updateExistingPivot(
                            $this->getOwnerRecord()->id,
                            [
                                'title' => $data['title'],
                                'type' => $data['type'],
                                'updated_at' => now(),
                            ]
                        );

                        // Sync the backing game's title to match the set title.
                        $backingGameId = (new ResolveBackingGameForAchievementSetAction())->execute($record->id);
                        if ($backingGameId) {
                            $backingGame = Game::find($backingGameId);
                            if ($backingGame && str_contains($backingGame->title, '[Subset -')) {
                                $baseTitle = trim(preg_replace('/\s*\[Subset\s*-.*\]$/', '', $backingGame->title));
                                $backingGame->title = "{$baseTitle} [Subset - {$data['title']}]";
                                $backingGame->save();
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title('Achievement set updated successfully')
                            ->send();
                    })
                    ->visible(fn () => $user->can('manage', GameAchievementSet::class))
                    ->hidden(fn (AchievementSet $record): bool => $record->type === AchievementSetType::Core->value),

                Actions\Action::make('details')
                    ->label('Details')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (AchievementSet $record): string => AchievementSetResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab()
                    ->visible(fn () => $user->can('manage', AchievementSet::class)),

                DetachAction::make()
                    ->visible(fn () => $user->can('delete', [GameAchievementSet::class, null]))
                    ->hidden(fn ($record) => $record->type === AchievementSetType::Core->value),
            ])
            ->toolbarActions([

            ])
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderBy('order_column')
                    ->orderBy('title', 'asc');
            });
    }

    private function hasWillBeTypes(Game $game): bool
    {
        return $game->gameAchievementSets()
            ->whereIn('type', [
                AchievementSetType::WillBeBonus->value,
                AchievementSetType::WillBeSpecialty->value,
            ])
            ->exists();
    }

    private function toggleMultisetTypes(Game $game): void
    {
        $willBeToFinal = [
            AchievementSetType::WillBeBonus->value => AchievementSetType::Bonus->value,
            AchievementSetType::WillBeSpecialty->value => AchievementSetType::Specialty->value,
        ];
        $finalToWillBe = array_flip($willBeToFinal);

        $isEnabling = $this->hasWillBeTypes($game);
        $mapping = $isEnabling ? $willBeToFinal : $finalToWillBe;

        foreach ($mapping as $from => $to) {
            $game->gameAchievementSets()
                ->where('type', $from)
                ->update(['type' => $to, 'updated_at' => now()]);
        }

        /** @var User $user */
        $user = Auth::user();
        $event = $isEnabling ? 'multisetEnabled' : 'multisetDisabled';
        $message = $isEnabling ? 'Multiset enabled' : 'Multiset disabled';
        activity()
            ->causedBy($user)
            ->performedOn($game)
            ->event($event)
            ->log($message);

        Notification::make()
            ->success()
            ->title($isEnabling ? 'Multiset enabled' : 'Multiset disabled')
            ->send();
    }
}
