<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmulatorResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EmulatorUserAgentsRelationManager extends RelationManager
{
    protected static string $relationship = 'userAgents';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->userAgents->count();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('client')
                    ->label('Client Identifier')
                    ->required()
                    ->placeholder('PCSX2')
                    ->helperText('The client string to match (eg: "RALibRetro", "Dolphin", "PCSX2")'),

                Forms\Components\TextInput::make('minimum_hardcore_version')
                    ->label('Minimum Hardcore Version')
                    ->placeholder('2.9.0')
                    ->helperText('⚠️ Versions older than this only support softcore mode. This is the minimum version required for hardcore to be enabled.'),

                Forms\Components\TextInput::make('minimum_allowed_version')
                    ->label('Minimum Allowed Version')
                    ->placeholder('2.7.0')
                    ->helperText('🔴 Versions older than this will be COMPLETELY BLOCKED from the server, even for softcore. Use this very sparingly, such as if a version of the emulator is DDoSing the server. Leave empty to allow all versions.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->description('Control which versions can connect to the server based on their user agent. Most emulators only need one entry.')
            ->columns([
                Tables\Columns\TextColumn::make('client')
                    ->label('Client Identifier')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('minimum_allowed_version')
                    ->label('Minimum Allowed Version')
                    ->placeholder('—')
                    ->tooltip('Versions older than this cannot connect to the server at all, even for softcore mode.')
                    ->formatStateUsing(fn ($state) => $state ?: 'No blocking'),

                Tables\Columns\TextColumn::make('minimum_hardcore_version')
                    ->label('Minimum Hardcore Version')
                    ->placeholder('—')
                    ->tooltip('Versions older than this can only play in softcore mode.')
                    ->formatStateUsing(fn ($state) => $state ?: 'No restriction'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add user agent')
                    ->modalHeading('Add user agent'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ]);
    }
}
