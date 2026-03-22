<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\PlaytestAwardResource\Pages;
use App\Filament\Resources\PlaytestAwardResource\RelationManagers\AwardedUsersRelationManager;
use App\Models\PlaytestAward;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class PlaytestAwardResource extends Resource
{
    protected static ?string $model = PlaytestAward::class;

    protected static ?string $modelLabel = 'Playtest Award';
    protected static ?string $pluralModelLabel = 'Playtest Awards';
    protected static ?string $breadcrumb = 'Playtest Awards';
    protected static string|BackedEnum|null $navigationIcon = 'fas-flask-vial';
    protected static string|UnitEnum|null $navigationGroup = 'Platform';
    protected static ?string $navigationLabel = 'Playtest Awards';
    protected static ?int $navigationSort = 56;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('label')
                    ->minLength(2)
                    ->maxLength(40)
                    ->required(),

                Schemas\Components\Section::make('Media')
                    ->icon('heroicon-s-photo')
                    ->schema([
                        Forms\Components\FileUpload::make('image_asset_path')
                            ->label('Badge')
                            ->disk('livewire-tmp')
                            ->image()
                            ->rules([
                                'dimensions:width=96,height=96',
                            ])
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->required(fn (?PlaytestAward $record): bool => $record === null)
                            ->previewable(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('badgeUrl')
                    ->label('Badge')
                    ->size(config('media.icon.md.width')),

                Tables\Columns\TextColumn::make('label')
                    ->label('Label'),

                Tables\Columns\TextColumn::make('badgeCount')
                    ->label('Awarded'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AwardedUsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\Index::route('/'),
            'create' => Pages\Create::route('/create'),
            'edit' => Pages\Edit::route('/{record}/edit'),
        ];
    }
}
