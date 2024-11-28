<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\RelationManagers;

use App\Filament\Resources\AchievementSetResource;
use App\Models\AchievementSet;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\User;
use App\Platform\Actions\AssociateAchievementSetToGameAction;
use App\Platform\Enums\AchievementSetType;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;
use Livewire\Component;

class AchievementSetsRelationManager extends RelationManager
{
    protected static string $relationship = 'achievementSets';

    protected static ?string $title = 'Sets';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

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
                Tables\Columns\TextColumn::make('title')
                    ->placeholder('Core Set')
                    ->url(function (AchievementSet $record) {
                        if (!request()->user()->can('manage', GameAchievementSet::class)) {
                            return null;
                        }

                        return AchievementSetResource::getUrl('view', ['record' => $record->id]);
                    }),

                Tables\Columns\TextColumn::make('type')
                    ->formatStateUsing(fn ($state): string => AchievementSetType::tryFrom($state)?->label())
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if ($state === AchievementSetType::WillBeBonus->value) {
                            return 'Will be Bonus when multiset goes live';
                        } elseif ($state === AchievementSetType::WillBeSpecialty->value) {
                            return 'Will be Specialty when multiset goes live';
                        } elseif ($state === AchievementSetType::WillBeExclusive->value) {
                            return 'Will be Exclusive when multiset goes live';
                        }

                        return null;
                    }),

                Tables\Columns\TextColumn::make('achievements_published')
                    ->label('Published Achievements'),

                Tables\Columns\TextColumn::make('achievements_unpublished')
                    ->label('Unpublished Achievements'),

                Tables\Columns\TextInputColumn::make('order_column')
                    ->label('Display Order')
                    ->rules([
                        'required',
                        'integer',
                        'min:1',
                    ])
                    ->disabled(fn ($record) => $record->type === AchievementSetType::Core->value),
            ])
            ->filters([

            ])
            ->headerActions([
                Tables\Actions\Action::make('attachSubset')
                    ->visible(fn () => $user->can('create', GameAchievementSet::class))
                    ->label('Attach Subset')
                    ->modalHeading('Attach Subset')
                    ->modifyWizardUsing(fn (Wizard $wizard) => $wizard->submitAction(
                        new HtmlString(Blade::render(<<<BLADE
                            <x-filament::button
                                type="submit"
                                size="sm"
                            >
                                Submit
                            </x-filament::button>
                        BLADE))
                    ))
                    ->steps([
                        Wizard\Step::make('Subset')
                            ->description('Find the subset in the database')
                            ->schema([
                                Select::make('game')
                                    ->label('Subset game')
                                    ->helperText('Find the current subset game in the database. For example: "Mega Man 2 [Subset - Bonus]".')
                                    ->searchable()
                                    ->options(
                                        Game::where('Title', 'like', "%[Subset -%")
                                            ->where('ConsoleID', $game->ConsoleID)
                                            ->where('achievements_published', '>', 0)
                                            ->whereDoesntHave('gameAchievementSets', function ($query) use ($attachedAchievementSetIds) {
                                                $query->core()->whereIn('achievement_set_id', $attachedAchievementSetIds);
                                            })
                                            ->orderBy('Title')
                                            ->get()
                                            ->mapWithKeys(function ($game) {
                                                return [$game->id => $game->title];
                                            })
                                            ->toArray()
                                    )
                                    ->required(),
                            ])
                            ->afterValidation(function (Component $livewire) {
                                // Try to set the default subset title based on the selected game.
                                $gameId = (int) $livewire->mountedTableActionsData[0]['game'];
                                $game = Game::find($gameId);

                                // Extract the default subset title, ie: the part after "[Subset - " and before "]".
                                $defaultSubsetTitle = '';
                                if (preg_match('/\[Subset - (.+?)\]/', $game->title, $matches)) {
                                    $defaultSubsetTitle = $matches[1];
                                }

                                $livewire->mountedTableActionsData[0]['title'] = $defaultSubsetTitle;

                                // Check if the achievement set is already attached to another game as a non-core type.
                                $legacySubsetAchievementSet = $game->gameAchievementSets()->core()->first()->achievementSet;
                                $attachedGame = $legacySubsetAchievementSet->games()
                                    ->wherePivot('type', '!=', AchievementSetType::Core->value)
                                    ->where('GameData.id', '!=', $game->id)
                                    ->first();

                                // Set the flag in the form data.
                                $livewire->mountedTableActionsData[0]['attachedToGameId'] = $attachedGame?->id;
                            }),

                        Wizard\Step::make('Details')
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

                                Select::make('type')
                                    ->options([
                                        AchievementSetType::WillBeBonus->value => AchievementSetType::Bonus->label(),
                                        AchievementSetType::WillBeSpecialty->value => AchievementSetType::Specialty->label(),
                                        AchievementSetType::WillBeExclusive->value => AchievementSetType::Exclusive->label(),
                                    ])
                                    ->helperText("
                                        Bonus loads with any hashes supported by Core.
                                        Specialty requires a unique hash, but also loads Core.
                                        Exclusive requires a unique hash, but does not load Core.
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
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->visible(fn () => $user->can('delete', [GameAchievementSet::class, null]))

                    // Core sets cannot be detached.
                    ->hidden(fn ($record) => $record->type === AchievementSetType::Core->value),
            ])
            ->bulkActions([

            ])
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderBy('order_column')
                    ->orderBy('title', 'asc');
            });
    }
}
