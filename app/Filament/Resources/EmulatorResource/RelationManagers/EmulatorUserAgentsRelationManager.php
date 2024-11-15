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

class EmulatorUserAgentsRelationManager extends RelationManager
{
    protected static string $relationship = 'userAgents';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('client'),

                Forms\Components\TextInput::make('minimum_allowed_version'),

                Forms\Components\TextInput::make('minimum_hardcore_version'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('client')
                    ->label('Client Identifier')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('minimum_allowed_version')
                    ->label('Minimum Allowed Version'),
                Tables\Columns\TextColumn::make('minimum_hardcore_version')
                    ->label('Minimum Hardcore Version'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ]);
    }
}
