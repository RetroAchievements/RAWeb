<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\EmulatorResource\Pages;
use App\Filament\Resources\EmulatorResource\RelationManagers\EmulatorCorePoliciesRelationManager;
use App\Filament\Resources\EmulatorResource\RelationManagers\EmulatorDownloadsRelationManager;
use App\Filament\Resources\EmulatorResource\RelationManagers\EmulatorPlatformsRelationManager;
use App\Filament\Resources\EmulatorResource\RelationManagers\EmulatorReleasesRelationManager;
use App\Filament\Resources\EmulatorResource\RelationManagers\EmulatorUserAgentsRelationManager;
use App\Filament\Resources\EmulatorResource\RelationManagers\SystemsRelationManager;
use App\Filament\Rules\DisallowHtml;
use App\Models\Emulator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists;
use Filament\Pages\Page;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class EmulatorResource extends Resource
{
    protected static ?string $model = Emulator::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-floppy-disk';

    protected static string|UnitEnum|null $navigationGroup = 'Releases';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    protected static int $globalSearchResultsLimit = 5;

    /**
     * @param Emulator $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'id' => $record->id,
            'name' => $record->name,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['id', 'name'];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Flex::make([
                    Schemas\Components\Section::make('Details')
                        ->schema([
                            Schemas\Components\Group::make()
                                ->columns(['xl' => 2, '2xl' => 2])
                                ->schema([
                                    Infolists\Components\TextEntry::make('name')
                                        ->label('Name')
                                        ->helperText('The name that will appear on the website.'),

                                    Infolists\Components\TextEntry::make('original_name')
                                        ->label('Original name')
                                        ->placeholder('none')
                                        ->helperText("The name of the emulator that was forked to create this emulator. eg: 'Snes9x' is the original name for 'RASnes9x'."),

                                    Infolists\Components\TextEntry::make('description')
                                        ->label('Description')
                                        ->placeholder('none')
                                        ->helperText("Private notes about the emulator."),

                                    Infolists\Components\TextEntry::make('documentation_url')
                                        ->label('Documentation URL')
                                        ->url(fn ($record) => $record->documentation_url)
                                        ->placeholder('none')
                                        ->helperText('A link to the documentation for the emulator.'),

                                    Infolists\Components\TextEntry::make('website_url')
                                        ->label('Website URL')
                                        ->url(fn ($record) => $record->website_url)
                                        ->placeholder('none')
                                        ->helperText('A link to the website for the emulator.'),

                                    Infolists\Components\TextEntry::make('source_url')
                                        ->label('Source code URL')
                                        ->url(fn ($record) => $record->source_url)
                                        ->placeholder('none')
                                        ->helperText('A link to the source code for the emulator.'),

                                    Infolists\Components\TextEntry::make('download_url')
                                        ->label('Download URL')
                                        ->url(fn ($record) => $record->download_url)
                                        ->placeholder('none')
                                        ->helperText('A link to download the emulator or to the downloads page for the emulator.'),

                                    Infolists\Components\TextEntry::make('download_x64_url')
                                        ->label('x64 Download URL')
                                        ->url(fn ($record) => $record->download_x64_url)
                                        ->placeholder('none')
                                        ->helperText('A link to download the Windows x64 version of the emulator.'),
                                ]),
                        ]),

                    Schemas\Components\Section::make([
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
                            ->label('Show on Downloads page')
                            ->boolean(),

                        Infolists\Components\IconEntry::make('can_debug_triggers')
                            ->label('Is toolkit available')
                            ->hintIcon(
                                'heroicon-m-question-mark-circle',
                                tooltip: 'If not enabled, warnings appear on the Downloads page and ticket creation page'
                            )
                            ->boolean(),
                    ])->grow(false),
                ])->from('md'),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Flex::make([
                    Schemas\Components\Section::make()
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('RASnes9x')
                                ->helperText('The name that will appear on the website.'),

                            Forms\Components\TextInput::make('original_name')
                                ->label('Original name')
                                ->maxLength(255)
                                ->placeholder('Snes9x')
                                ->helperText("If this emulator is forked from another emulator, put the original emulator's name here. eg: 'Snes9x' is the original name for 'RASnes9x'."),

                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                ->rules([new DisallowHtml()])
                                ->rows(5)
                                ->helperText("This text used to be displayed on the Downloads page, but isn't anymore. You can add notes about the emulator here."),

                            Forms\Components\TextInput::make('documentation_url')
                                ->label('Documentation URL')
                                ->url()
                                ->placeholder('https://pcsx2.net/docs/')
                                ->helperText('A link to the documentation for the emulator.'),

                            Forms\Components\TextInput::make('website_url')
                                ->label('Website URL')
                                ->url()
                                ->placeholder('https://pcsx2.net/')
                                ->helperText('A link to the website for the emulator.'),

                            Forms\Components\TextInput::make('source_url')
                                ->label('Source code URL')
                                ->url()
                                ->placeholder('https://github.com/RetroAchievements/RALibretro')
                                ->helperText('A link to the source code for the emulator.'),

                            Forms\Components\TextInput::make('download_url')
                                ->label('Download URL')
                                ->url()
                                ->required()
                                ->placeholder('https://pcsx2.net/downloads')
                                ->helperText('A link to download the emulator or to the downloads page for the emulator.'),

                            Forms\Components\TextInput::make('download_x64_url')
                                ->label('x64 Download URL')
                                ->url()
                                ->helperText('A link to download the Windows x64 version of the emulator.'),
                        ]),

                    Schemas\Components\Section::make()
                        ->grow(false)
                        ->schema([
                            Forms\Components\Toggle::make('active')
                                ->label('Show on Downloads page'),

                            Forms\Components\Toggle::make('can_debug_triggers')
                                ->label('Is toolkit available')
                                ->hintIcon(
                                    'heroicon-m-question-mark-circle',
                                    tooltip: 'If not enabled, warnings appear on the Downloads page and ticket creation page'
                                ),
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

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->grow(true),

                Tables\Columns\TextColumn::make('user_agents_count')
                    ->label('User Agents')
                    ->counts('userAgents')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('latestRelease.version')
                    ->label('Latest Version'),

                Tables\Columns\TextColumn::make('minimumSupportedRelease.version')
                    ->label('Minimum Allowed Version'),

                Tables\Columns\TextColumn::make('original_name')
                    ->label('Original Name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->default(false)
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('can_debug_triggers')
                    ->label('Can Debug Triggers')
                    ->boolean()
                    ->default(false)
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->paginated(false)
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->deferFilters()
            ->recordActions([
                ActionGroup::make([
                    ActionGroup::make([
                        ViewAction::make(),
                        EditAction::make(),
                        DeleteAction::make(),
                        RestoreAction::make(),
                    ])->dropdown(false),

                    Action::make('audit-log')
                        ->url(fn ($record) => EmulatorResource::getUrl('audit-log', ['record' => $record]))
                        ->icon('fas-clock-rotate-left'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SystemsRelationManager::class,
            EmulatorReleasesRelationManager::class,
            EmulatorUserAgentsRelationManager::class,
            EmulatorCorePoliciesRelationManager::class,
            EmulatorPlatformsRelationManager::class,
            EmulatorDownloadsRelationManager::class,
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
