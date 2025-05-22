<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\RelationManagers;

use App\Filament\Resources\GameResource;
use App\Models\Game;
use App\Models\GameRelease;
use App\Models\User;
use App\Platform\Enums\GameReleaseRegion;
use App\Platform\Enums\ReleasedAtGranularity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ReleasesRelationManager extends RelationManager
{
    protected static string $relationship = 'releases';

    protected static ?string $recordTitleAttribute = 'title';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        /** @var User $user */
        $user = Auth::user();

        if ($ownerRecord instanceof Game) {
            return $user->can('manage', $ownerRecord);
        }

        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(80)
                    ->label('Title'),

                Forms\Components\Toggle::make('is_canonical_game_title')
                    ->label('Is Canonical Title')
                    ->helperText('If enabled, this will be used as the main title for the game on game pages and game lists. Only one title per game can be canonical.')
                    ->disabled(fn (?GameRelease $record) => $record && $record->is_canonical_game_title),

                Forms\Components\Select::make('region')
                    ->searchable()
                    ->options([
                        'Common Regions' => [
                            GameReleaseRegion::NorthAmerica->value => GameReleaseRegion::NorthAmerica->label(),
                            GameReleaseRegion::Japan->value => GameReleaseRegion::Japan->label(),
                            GameReleaseRegion::Europe->value => GameReleaseRegion::Europe->label(),
                            GameReleaseRegion::Worldwide->value => GameReleaseRegion::Worldwide->label(),
                        ],
                        'Other Regions' => collect(GameReleaseRegion::cases())
                            ->filter(fn (GameReleaseRegion $region) => !in_array($region, [
                                GameReleaseRegion::NorthAmerica,
                                GameReleaseRegion::Japan,
                                GameReleaseRegion::Europe,
                                GameReleaseRegion::Worldwide,
                                GameReleaseRegion::Other,
                            ]))
                            ->mapWithKeys(fn (GameReleaseRegion $region) => [$region->value => $region->label()])
                            ->toArray(),
                    ])
                    ->columnSpan(2)
                    ->label('Region'),

                Forms\Components\DatePicker::make('released_at')
                    ->label('Release Date')
                    ->native(false)
                    ->minDate('1970-01-01')
                    ->maxDate(now())
                    ->displayFormat('F j, Y')
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        // Set the granularity to 'day' if this is the first time released_at is set.
                        if (!empty($state)) {
                            $set('released_at_granularity', 'day');
                        }
                    }),

                Forms\Components\ToggleButtons::make('released_at_granularity')
                    ->label('Release Date Precision')
                    ->options([
                        ReleasedAtGranularity::Day->value => 'Day',
                        ReleasedAtGranularity::Month->value => 'Month',
                        ReleasedAtGranularity::Year->value => 'Year',
                    ])
                    ->inline()
                    ->reactive()
                    ->required(fn (callable $get) => !empty($get('released_at'))),

                Forms\Components\Placeholder::make('released_at_display')
                    ->label('Preview')
                    ->content(function (Get $get): string {
                        $releasedAt = $get('released_at');
                        $releasedAtGranularity = $get('released_at_granularity');

                        if (!$releasedAt) {
                            return 'No release date.';
                        }

                        switch ($releasedAtGranularity) {
                            case 'year':
                                return Carbon::parse($releasedAt)->format('Y');

                            case 'month':
                                return Carbon::parse($releasedAt)->format('F Y');

                            default:
                                return Carbon::parse($releasedAt)->format('F j, Y');
                        }
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('released_at')
                    ->label('Release Date')
                    ->placeholder('unknown')
                    ->formatStateUsing(function (GameRelease $gameRelease): ?string {
                        $releasedAt = $gameRelease->released_at;
                        $releasedAtGranularity = $gameRelease->released_at_granularity;

                        if (!$releasedAt) {
                            return null;
                        }

                        switch ($releasedAtGranularity) {
                            case ReleasedAtGranularity::Year:
                                return Carbon::parse($releasedAt)->format('Y');

                            case ReleasedAtGranularity::Month:
                                return Carbon::parse($releasedAt)->format('F Y');

                            default:
                                return Carbon::parse($releasedAt)->format('F j, Y');
                        }
                    })
                    ->sortable(query: function ($query, string $direction): Builder {
                        return $query
                            /** @see BuildsGameListQueries.php */
                            ->selectRaw(<<<SQL
                                *,
                                CASE
                                    WHEN released_at_granularity = 'year' THEN
                                        DATE(CONCAT(SUBSTR(released_at, 1, 4), '-01-01'))
                                    WHEN released_at_granularity = 'month' THEN
                                        DATE(CONCAT(SUBSTR(released_at, 1, 7), '-01'))
                                    ELSE
                                        COALESCE(released_at, '9999-12-31')
                                END AS normalized_released_at,
                                CASE released_at_granularity
                                    WHEN 'year' THEN 1
                                    WHEN 'month' THEN 2
                                    WHEN 'day' THEN 3
                                    ELSE 4
                                END AS granularity_order
                            SQL)
                            // Ensure NULL release dates always sort to the end, regardless of sort direction.
                            ->orderByRaw('released_at IS NULL')
                            ->orderBy('normalized_released_at', $direction)
                            ->orderBy('granularity_order', $direction);
                    }),

                Tables\Columns\TextColumn::make('region')
                    ->formatStateUsing(function ($state): ?string {
                        if (!$state) {
                            return null;
                        }

                        if ($state instanceof GameReleaseRegion) {
                            return $state->label();
                        }

                        return GameReleaseRegion::tryFrom($state)?->label() ?? $state;
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_canonical_game_title')
                    ->label('Is Canonical Title')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([

            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->authorize(function () {
                        /** @var User $user */
                        $user = Auth::user();
                        $game = $this->getOwnerRecord();

                        return $user->can('create', [GameRelease::class, $game]);
                    })
                    ->mutateFormDataUsing(function (array $data) {
                        // If this is marked as canonical, unmark any other canonical titles.
                        if ($data['is_canonical_game_title'] ?? false) {
                            GameRelease::where('game_id', $this->getOwnerRecord()->id)
                                ->where('is_canonical_game_title', true)
                                ->update(['is_canonical_game_title' => false]);
                        }

                        return $data;
                    })
                    ->after(function (GameRelease $record) {
                        // If the new title is canonical, update the main game title.
                        if ($record->is_canonical_game_title) {
                            /** @var Game $game */
                            $game = $this->getOwnerRecord();
                            $game->Title = $record->title;
                            $game->save();

                            // Redirect to refresh the page and show the updated title.
                            $this->redirect(GameResource::getUrl('view', ['record' => $game, 'activeRelationManager' => 2]));
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data, GameRelease $record) {
                        // If this is now marked as canonical and wasn't before, unmark any other canonical titles.
                        if (($data['is_canonical_game_title'] ?? false) && !$record->is_canonical_game_title) {
                            GameRelease::where('game_id', $this->getOwnerRecord()->id)
                                ->where('id', '!=', $record->id)
                                ->where('is_canonical_game_title', true)
                                ->update(['is_canonical_game_title' => false]);
                        }

                        return $data;
                    })
                    ->after(function (GameRelease $record) {
                        // If this title is canonical, update the main game title.
                        if ($record->is_canonical_game_title) {
                            /** @var Game $game */
                            $game = $this->getOwnerRecord();
                            $game->Title = $record->title;
                            $game->save();

                            // Redirect to refresh the page and show the updated title.
                            $this->redirect(GameResource::getUrl('view', ['record' => $game, 'activeRelationManager' => 2]));
                        }
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([

            ])
            ->defaultSort('released_at', 'asc');
    }
}
