<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\GameHashResource\Pages;
use App\Models\GameHash;
use Filament\Forms\Form;
use Filament\Support\Enums\FontFamily;
use Filament\Tables;
use Filament\Tables\Table;

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
                    ->url(fn (GameHash $record): string => route('game.hash.manage', ['game' => $record->game])),

                Tables\Columns\TextColumn::make('game.title')
                    ->label('Game')
                    ->searchable()
                    ->url(fn (GameHash $record): string => route('game.show', ['game' => $record->game])),

                Tables\Columns\TextColumn::make('user.display_name')
                    ->url(fn (GameHash $record): ?string => $record->user ? route('user.show', ['user' => $record->user]) : null),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date Linked')
                    ->dateTime(),
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
}
