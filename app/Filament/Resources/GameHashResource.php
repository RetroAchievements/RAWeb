<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\GameHashResource\Pages;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\System;
use Filament\Forms\Form;
use Filament\Support\Enums\FontFamily;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GameHashResource extends Resource
{
    protected static ?string $model = GameHash::class;

    protected static ?string $navigationIcon = 'fas-file-archive';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // TODO
            ]);
    }

    // TODO link values to respective pages in Filament once created
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('md5')
                    ->label('MD5')
                    ->searchable()
                    ->fontFamily(FontFamily::Mono)
                    ->url(fn (GameHash $record): string => route('game.hash.manage', ['game' => $record->game]))
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('game.title')
                    ->label('Game')
                    ->searchable()
                    ->url(function (GameHash $record) {
                        if (request()->user()->can('manage', Game::class)) {
                            return GameResource::getUrl('view', ['record' => $record->game]);
                        }

                        return route('game.show', ['game' => $record->game]);
                    }),

                Tables\Columns\TextColumn::make('game.system')
                    ->label('System')
                    ->formatStateUsing(fn (System $state) => "[{$state->id}] {$state->name}")
                    ->url(function (GameHash $record) {
                        if (request()->user()->can('manage', System::class)) {
                            return SystemResource::getUrl('view', ['record' => $record->id]);
                        }

                        return null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('game.achievements_published')
                    ->label('Achievements (Published)')
                    ->numeric()
                    ->alignEnd()
                    ->default(0)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user.display_name')
                    ->label('Linked By')
                    ->url(fn (GameHash $record): ?string => $record->user ? route('user.show', ['user' => $record->user]) : null)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date Linked')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([

            ])
            ->actions([
                // TODO
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([

            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(50)
            ->searchPlaceholder('Search (Hash, Game)');
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\Index::route('/'),
            // TODO
            // 'create' => Pages\Create::route('/create'),
            // 'edit' => Pages\Edit::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<GameHash>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['game.system']);
    }
}
