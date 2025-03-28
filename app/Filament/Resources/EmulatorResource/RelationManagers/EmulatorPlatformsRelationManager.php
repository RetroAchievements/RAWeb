<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmulatorResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmulatorPlatformsRelationManager extends RelationManager
{
    protected static string $relationship = 'platforms';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->platforms->count();
    }

    public function function(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('execution_environment')
                    ->label('Environment')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state->label()),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderBy('execution_environment')->orderBy('name');
            });
    }
}
