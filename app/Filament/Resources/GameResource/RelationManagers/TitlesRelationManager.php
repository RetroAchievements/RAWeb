<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\RelationManagers;

use App\Filament\Resources\GameResource;
use App\Models\Game;
use App\Models\GameTitle;
use App\Models\User;
use App\Platform\Enums\GameTitleRegion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TitlesRelationManager extends RelationManager
{
    protected static string $relationship = 'titles';

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

                Forms\Components\Select::make('region')
                    ->searchable()
                    ->options([
                        'Common Regions' => [
                            GameTitleRegion::NorthAmerica->value => GameTitleRegion::NorthAmerica->label(),
                            GameTitleRegion::Japan->value => GameTitleRegion::Japan->label(),
                            GameTitleRegion::Europe->value => GameTitleRegion::Europe->label(),
                            GameTitleRegion::Worldwide->value => GameTitleRegion::Worldwide->label(),
                        ],
                        'Other Regions' => collect(GameTitleRegion::cases())
                            ->filter(fn (GameTitleRegion $region) => !in_array($region, [
                                GameTitleRegion::NorthAmerica,
                                GameTitleRegion::Japan,
                                GameTitleRegion::Europe,
                                GameTitleRegion::Worldwide,
                                GameTitleRegion::Other,
                            ]))
                            ->mapWithKeys(fn (GameTitleRegion $region) => [$region->value => $region->label()])
                            ->toArray(),
                        'Special' => [
                            GameTitleRegion::Other->value => GameTitleRegion::Other->label(),
                        ],
                    ])
                    ->label('Region'),

                Forms\Components\Toggle::make('is_canonical')
                    ->label('Canonical Title')
                    ->helperText('If checked, this will be used as the main title for the game. Only one title per game can be canonical.')
                    ->disabled(fn (?GameTitle $record) => $record && $record->is_canonical),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('region')
                    ->formatStateUsing(function ($state): ?string {
                        if (!$state) {
                            return null;
                        }

                        if ($state instanceof GameTitleRegion) {
                            return $state->label();
                        }

                        return GameTitleRegion::tryFrom($state)?->label() ?? $state;
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_canonical')
                    ->label('Canonical')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->authorize(function () {
                        /** @var User $user */
                        $user = Auth::user();
                        $game = $this->getOwnerRecord();

                        return $user->can('create', [GameTitle::class, $game]);
                    })
                    ->mutateFormDataUsing(function (array $data) {
                        // If this is marked as canonical, unmark any other canonical titles.
                        if ($data['is_canonical'] ?? false) {
                            GameTitle::where('game_id', $this->getOwnerRecord()->id)
                                ->where('is_canonical', true)
                                ->update(['is_canonical' => false]);
                        }

                        return $data;
                    })
                    ->after(function (GameTitle $record) {
                        // If the new title is canonical, update the main game title.
                        if ($record->is_canonical) {
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
                    ->mutateFormDataUsing(function (array $data, GameTitle $record) {
                        // If this is now marked as canonical and wasn't before, unmark any other canonical titles.
                        if (($data['is_canonical'] ?? false) && !$record->is_canonical) {
                            GameTitle::where('game_id', $this->getOwnerRecord()->id)
                                ->where('id', '!=', $record->id)
                                ->where('is_canonical', true)
                                ->update(['is_canonical' => false]);
                        }

                        return $data;
                    })
                    ->after(function (GameTitle $record) {
                        // If this title is canonical, update the main game title.
                        if ($record->is_canonical) {
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
            ->defaultSort('is_canonical', 'desc');
    }
}
