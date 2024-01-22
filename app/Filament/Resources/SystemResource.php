<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\SystemResource\Pages;
use App\Platform\Models\System;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SystemResource extends Resource
{
    protected static ?string $model = System::class;

    protected static ?string $navigationIcon = 'fas-computer';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 1;

    protected static int $globalSearchResultsLimit = 5;

    /**
     * @param System $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name_full;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'ID' => $record->ID,
            'Short name' => $record->name_short,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['ID', 'name_full', 'name_short'];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(1)
            ->schema([
                Infolists\Components\Split::make([
                    Infolists\Components\Section::make('Details')
                        ->columns(['xl' => 2, '2xl' => 2])
                        ->schema([
                            Infolists\Components\Group::make()
                                ->schema([
                                    Infolists\Components\ImageEntry::make('icon_url')
                                        ->label('Icon')
                                        ->size(config('media.icon.lg.width')),
                                ]),
                            Infolists\Components\Group::make()
                                ->schema([
                                    Infolists\Components\TextEntry::make('Name')
                                        ->helperText('Used in menus and page titles. May include manufacturer for recognizability.'),
                                    Infolists\Components\TextEntry::make('name_short')
                                        ->label('Short name')
                                        ->helperText('Used in condensed lists and to determine icon image name.'),
                                    Infolists\Components\TextEntry::make('name_full')
                                        ->label('Full name')
                                        ->helperText('Manufacturer + name. Name might not include manufacturer.'),
                                    Infolists\Components\TextEntry::make('manufacturer')
                                        ->helperText('Manufacturer company name.'),
                                ]),
                            // Infolists\Components\Group::make()
                            //     ->schema([
                            //         Infolists\Components\TextEntry::make('canonical_url')
                            //             ->label('Canonical URL')
                            //             ->url(fn (System $record): string => $record->getCanonicalUrlAttribute()),
                            //         Infolists\Components\TextEntry::make('permalink')
                            //             ->url(fn (System $record): string => $record->getPermalinkAttribute()),
                            //     ]),
                        ]),
                    Infolists\Components\Section::make([
                        Infolists\Components\TextEntry::make('Created')
                            ->label('Created at')
                            ->dateTime()
                            ->hidden(fn ($state) => !$state),
                        Infolists\Components\TextEntry::make('Updated')
                            ->label('Updated at')
                            ->dateTime()
                            ->hidden(fn ($state) => !$state),
                        Infolists\Components\IconEntry::make('active')
                            ->boolean(),
                    ])->grow(false),
                ])->from('md'),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Split::make([
                    Forms\Components\Section::make()
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('Name')
                                ->helperText('Used in menus and page titles. May include manufacturer for recognizability.')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('name_short')
                                ->label('Short name')
                                ->helperText('Used in condensed lists and to determine icon image name.')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('manufacturer')
                                ->helperText('Manufacturer company name.')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('name_full')
                                ->label('Full name')
                                ->helperText('Manufacturer + name. Name might not include manufacturer.')
                                ->maxLength(255),
                        ]),
                    Forms\Components\Section::make()
                        ->grow(false)
                        ->schema([
                            Forms\Components\Toggle::make('active'),
                        ]),
                ])->from('md'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon_url')
                    ->label('')
                    ->size(config('media.icon.sm.width')),
                Tables\Columns\TextColumn::make('ID')
                    ->label('ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_full')
                    ->label('Full name')
                    ->description(fn (System $record): ?string => $record->name_short)
                    ->searchable()
                    ->grow(true),
                Tables\Columns\TextColumn::make('name_short')
                    ->label('Short name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('manufacturer')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('Name')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->default(false)
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('Created')
                    ->label('Created at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('Updated')
                    ->label('Updated at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginated(false)
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewSystem::class,
            Pages\ListSystemAuditLog::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystems::route('/'),
            'create' => Pages\CreateSystem::route('/create'),
            'view' => Pages\ViewSystem::route('/{record}'),
            'edit' => Pages\EditSystem::route('/{record}/edit'),
            'audit-log' => Pages\ListSystemAuditLog::route('/{record}/audit-log'),
        ];
    }

    /**
     * @return Builder<System>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
