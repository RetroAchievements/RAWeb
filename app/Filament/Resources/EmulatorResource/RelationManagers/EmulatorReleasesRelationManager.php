<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmulatorResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmulatorReleasesRelationManager extends RelationManager
{
    protected static string $relationship = 'releases';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('version')
                    ->label('Version')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Release Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('stable')
                    ->boolean()
                    ->default(false)
                    ->alignCenter(),
                Tables\Columns\IconColumn::make('minimum')
                    ->boolean()
                    ->default(false)
                    ->alignCenter(),
            ])
            ->filters([

            ])
            ->headerActions([

            ])
            ->actions([

            ])
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderByDesc('created_at');
            });
    }
}
