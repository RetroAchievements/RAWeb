<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmulatorResource\RelationManagers;

use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas;
use Filament\Schemas\Schema;
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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('client')
                    ->label('Client Identifier')
                    ->required()
                    ->placeholder('PCSX2')
                    ->helperText('The client string to match (eg: "RALibRetro", "Dolphin", "PCSX2")'),

                Schemas\Components\Section::make('Minimum versions')
                    ->icon('heroicon-s-shield-exclamation')
                    ->schema([
                        Forms\Components\TextInput::make('minimum_hardcore_version')
                            ->label('Minimum Hardcore Version')
                            ->placeholder('2.9.0')
                            ->helperText('⚠️ Versions older than this only support casual mode. This is the minimum version required for hardcore to be enabled.'),

                        Forms\Components\TextInput::make('minimum_allowed_version')
                            ->label('Minimum Allowed Version')
                            ->placeholder('2.7.0')
                            ->helperText('🔴 Versions older than this will be COMPLETELY BLOCKED from the server, even for casual. Use this very sparingly, such as if a version of the emulator is DDoSing the server. Leave empty to allow all versions.'),

                        Forms\Components\TextInput::make('pending_minimum_hardcore_version')
                            ->label('Next Minimum Hardcore Version')
                            ->placeholder('2.10.0')
                            ->helperText('Will become the minimum hardcore version on the specified date.'),

                        Forms\Components\DatePicker::make('pending_minimum_hardcore_version_at')
                            ->label('Next Minimum Hardcore Version Cutover')
                            ->helperText('When the next minimum hardcore version will become the minimum hardcore version.'),
                    ])
                    ->columns(2),
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
                    ->tooltip('Versions older than this cannot connect to the server at all, even for casual mode.')
                    ->formatStateUsing(fn ($state) => $state ?: 'No blocking'),

                Tables\Columns\TextColumn::make('minimum_hardcore_version')
                    ->label('Minimum Hardcore Version')
                    ->placeholder('—')
                    ->tooltip(fn ($record) => 'Versions older than this can only play in casual mode.' .
                        ($record->pending_minimum_hardcore_version_at
                            ? (' This will change to ' . $record->pending_minimum_hardcore_version . ' in ' . floor($record->pending_minimum_hardcore_version_at->diffInDays(now(), true)) . ' days.')
                            : '')
                    )
                    ->formatStateUsing(fn ($state) => $state ?: 'No restriction')
                    ->icon(fn ($record) => $record->pending_minimum_hardcore_version_at ? 'fas-circle-arrow-up' : null)
                    ->iconPosition('after'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add user agent')
                    ->modalHeading('Add user agent'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
