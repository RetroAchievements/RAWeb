<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmulatorResource\RelationManagers;

use App\Models\Platform;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EmulatorDownloadsRelationManager extends RelationManager
{
    protected static string $relationship = 'downloads';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->downloads->count();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('platform_id')
                    ->label('Platform')
                    ->options(function () {
                        return Platform::query()
                            ->get()
                            ->mapWithKeys(fn ($platform) => [$platform->id => $platform->name]);
                    }),

                Forms\Components\TextInput::make('url')
                    ->label('URL'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('platform.name')
                    ->label('Platform')
                    ->sortable(),

                Tables\Columns\TextColumn::make('url')
                    ->label('URL'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->emptyStateDescription('Emulator download records are optional overrides for default download link(s) on a platform-specific basis.');
    }
}
