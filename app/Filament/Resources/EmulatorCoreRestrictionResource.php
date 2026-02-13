<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ClientSupportLevel;
use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\EmulatorCoreRestrictionResource\Pages;
use App\Models\EmulatorCoreRestriction;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class EmulatorCoreRestrictionResource extends Resource
{
    protected static ?string $model = EmulatorCoreRestriction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static string|UnitEnum|null $navigationGroup = 'Releases';
    protected static ?int $navigationSort = 25;
    protected static ?string $navigationLabel = 'Core Restrictions';
    protected static ?string $modelLabel = 'Core Restriction';
    protected static ?string $pluralModelLabel = 'Core Restrictions';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('core_name')
                    ->label('Core Name')
                    ->required()
                    ->maxLength(80)
                    ->placeholder('dolphin')
                    ->helperText('Lowercase core identifier as it appears before "_libretro" in user agent strings. This is an exact match.'),

                Forms\Components\Select::make('support_level')
                    ->label('Support Level')
                    ->required()
                    ->options([
                        ClientSupportLevel::Warned->value => 'Warned (warning shown, hardcore and leaderboards allowed)',
                        ClientSupportLevel::Unsupported->value => 'Unsupported (softcore only, warning shown)',
                        ClientSupportLevel::Blocked->value => 'Blocked (cannot earn achievements at all)',
                    ])
                    ->helperText('Use Warned for minor issues, Unsupported for cores that should not earn hardcore, and Blocked for cores with major debilitating issues.'),

                Forms\Components\TextInput::make('recommendation')
                    ->label('Recommendation')
                    ->maxLength(255)
                    ->placeholder('Please use standalone Dolphin instead.')
                    ->helperText('If set, this text is appended to the warning message shown to the player in their emulator.'),

                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->helperText('Internal staff notes about why this restriction exists.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                        ClientSupportLevel::Warned => 'Warned',
                        default => $state->name ?? 'Unknown',
                    })
                    ->color(fn ($state) => match ($state) {
                        ClientSupportLevel::Blocked => 'danger',
                        ClientSupportLevel::Unsupported => 'warning',
                        ClientSupportLevel::Warned => 'info',
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
            ->defaultSort('core_name')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
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
