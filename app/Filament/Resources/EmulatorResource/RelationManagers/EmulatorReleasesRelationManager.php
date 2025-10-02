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

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->releases->count();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('version')
                    ->placeholder('2.4.0')
                    ->helperText('eg: "2.4.0" or "2407-68"')
                    ->required(),

                Forms\Components\DatePicker::make('created_at')
                    ->label('Release Date')
                    ->helperText('When was this version of the emulator released?')
                    ->native(false)
                    ->date()
                    ->required(),

                Forms\Components\Select::make('stable')
                    ->label('Release Type')
                    ->options([
                        '1' => 'Stable',
                        '0' => 'Pre-release',
                    ])
                    ->default('1')
                    ->required()
                    ->helperText('Stable releases can be set as minimum and are reported via the Connect API. Pre-release releases are experimental.'),

                Forms\Components\Toggle::make('minimum')
                    ->label('Set as recommended minimum version')
                    ->helperText('Sets the minimum version for hardcore mode (enforced by the toolkit DLL). Users with older versions can still connect but cannot use hardcore. To block connections entirely, use the User Agents tab.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->description('Manage emulator version information reported to clients. The minimum version blocks hardcore mode for DLL-based clients only (purely informational for other clients). To block connections entirely, use the User Agents tab.')
            ->columns([
                Tables\Columns\TextColumn::make('version')
                    ->label('Version')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Release Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('stable')
                    ->label('Type')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Stable' : 'Pre-release')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning'),

                Tables\Columns\IconColumn::make('minimum')
                    ->label('Recommended Minimum')
                    ->alignCenter()
                    ->tooltip(fn ($record): ?string => $record->minimum ? 'This version is the minimum for hardcore mode (DLL-enforced). Does not block connections.' : null)
                    ->boolean()
                    ->getStateUsing(fn ($record): string => $record->minimum ? 'true' : '')
                    ->trueIcon('heroicon-o-information-circle')
                    ->falseIcon(null)
                    ->trueColor('info'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add release')
                    ->modalHeading('Add release'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderByDesc('created_at');
            });
    }
}
