<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmulatorResource\RelationManagers;

use App\Enums\ClientSupportLevel;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EmulatorCorePoliciesRelationManager extends RelationManager
{
    protected static string $relationship = 'corePolicies';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->corePolicies->count();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('core_name')
                    ->label('Core Name')
                    ->required()
                    ->maxLength(80)
                    ->placeholder('dolphin')
                    ->helperText('Lowercase core identifier prefix as it appears before "_libretro" in user agent strings. This is a prefix match. "doublecherry" will also match "doublecherrygb".'),

                Forms\Components\Select::make('support_level')
                    ->label('Support Level')
                    ->required()
                    ->options([
                        ClientSupportLevel::Unsupported->value => 'Unsupported (softcore only, warning shown)',
                        ClientSupportLevel::Blocked->value => 'Blocked (cannot earn achievements at all)',
                    ])
                    ->helperText('When in doubt, use Unsupported. Only use Blocked if the core has major debilitating issues.'),

                Forms\Components\TextInput::make('recommendation')
                    ->label('Recommendation')
                    ->maxLength(255)
                    ->placeholder('Please use standalone Dolphin instead.')
                    ->helperText('If set, this text is appended to the warning message shown to the player in their emulator.'),

                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->helperText('Internal staff notes about why this policy exists.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('core_name')
            ->description('Override support levels for specific emulator cores. Use this to block or restrict problematic cores from this emulator.')
            ->columns([
                Tables\Columns\TextColumn::make('core_name')
                    ->label('Core Name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('support_level')
                    ->label('Support Level')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        ClientSupportLevel::Blocked => 'Blocked',
                        ClientSupportLevel::Unsupported => 'Unsupported',
                        default => $state->name ?? 'Unknown',
                    })
                    ->color(fn ($state) => match ($state) {
                        ClientSupportLevel::Blocked => 'danger',
                        ClientSupportLevel::Unsupported => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('recommendation')
                    ->label('Recommendation')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->placeholder('—')
                    ->limit(50),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created at')
                    ->dateTime(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add core policy')
                    ->modalHeading('Add core policy'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
