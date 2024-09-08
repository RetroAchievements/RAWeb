<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\EmulatorResource\Pages;
use App\Filament\Resources\EmulatorResource\RelationManagers\SystemsRelationManager;
use App\Models\Emulator;
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

class EmulatorResource extends Resource
{
    protected static ?string $model = Emulator::class;

    protected static ?string $navigationIcon = 'fas-floppy-disk';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'handle';

    protected static int $globalSearchResultsLimit = 5;

    /**
     * @param Emulator $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name_full;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'ID' => $record->ID,
            'Handle' => $record->handle,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['ID', 'handle'];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(1)
            ->schema([
                Infolists\Components\Split::make([
                    Infolists\Components\Section::make('Details')
                        ->schema([
                            Infolists\Components\Group::make()
                                ->columns(['xl' => 2, '2xl' => 2])
                                ->schema([
                                    Infolists\Components\TextEntry::make('handle')
                                        ->label('Name')
                                        ->helperText('Name of emulator'),
                                    Infolists\Components\TextEntry::make('name')
                                        ->label('Original name')
                                        ->helperText('Original name of emulator.'),
                                    Infolists\Components\TextEntry::make('description')
                                        ->label('Description')
                                        ->helperText('Additional text to display on the download page.'),
                                    Infolists\Components\TextEntry::make('link')
                                        ->label('Documentation link')
                                        ->helperText('Link to emulator documentation.'),
                                ]),
                        ]),
                    Infolists\Components\Section::make([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),
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
                            Forms\Components\TextInput::make('handle')
                                ->label('Name')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Name of emulator'),
                            Forms\Components\TextInput::make('name')
                                ->label('Original name')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Original name of emulator.'),
                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                ->rows(5)
                                ->helperText('Additional text to display on the download page.'),
                            Forms\Components\TextInput::make('link')
                                ->label('Documentation link')
                                ->url()
                                ->helperText('Link to emulator documentation.'),
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
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('handle')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->grow(true),
                Tables\Columns\TextColumn::make('name')
                    ->label('Original Name')
                    ->searchable()
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
            ->defaultSort('handle')
            ->paginated(false)
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->deferFilters()
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ActionGroup::make([
                        Tables\Actions\ViewAction::make(),
                        Tables\Actions\EditAction::make(),
                        Tables\Actions\DeleteAction::make(),
                        Tables\Actions\RestoreAction::make(),
                    ])->dropdown(false),
                    Tables\Actions\Action::make('audit-log')
                        ->url(fn ($record) => EmulatorResource::getUrl('audit-log', ['record' => $record]))
                        ->icon('fas-clock-rotate-left'),
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
            SystemsRelationManager::class,
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\Details::class,
            Pages\AuditLog::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\Index::route('/'),
            'create' => Pages\Create::route('/create'),
            'view' => Pages\Details::route('/{record}'),
            'edit' => Pages\Edit::route('/{record}/edit'),
            'audit-log' => Pages\AuditLog::route('/{record}/audit-log'),
        ];
    }

    /**
     * @return Builder<Emulator>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
