<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\SystemResource\Pages;
use App\Models\System;
use App\Models\User;
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
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class SystemResource extends Resource
{
    protected static ?string $model = System::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-computer';

    protected static string|UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name_full';

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
            'id' => $record->id,
            'Short name' => $record->name_short,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['id', 'name_full', 'name_short'];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Flex::make([
                    Schemas\Components\Section::make('Details')
                        ->columns(['xl' => 2, '2xl' => 2])
                        ->schema([
                            Schemas\Components\Group::make()
                                ->schema([
                                    Infolists\Components\ImageEntry::make('icon_url')
                                        ->label('Icon')
                                        ->size(config('media.icon.lg.width')),
                                ]),
                            Schemas\Components\Group::make()
                                ->schema([
                                    Infolists\Components\TextEntry::make('name')
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
                        ]),
                    Schemas\Components\Section::make([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created at')
                            ->dateTime()
                            ->hidden(fn ($state) => !$state),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Updated at')
                            ->dateTime()
                            ->hidden(fn ($state) => !$state),

                        Infolists\Components\IconEntry::make('active')
                            ->boolean(),
                    ])->grow(false),
                ])->from('md'),

                Schemas\Components\Section::make('Screenshots')
                    ->columns(['xl' => 2, '2xl' => 2])
                    ->schema([
                        Infolists\Components\IconEntry::make('has_analog_tv_output')
                            ->label('Has analog TV output')
                            ->boolean(),

                        Infolists\Components\IconEntry::make('supports_upscaled_screenshots')
                            ->label('Supports upscaled screenshots')
                            ->boolean(),

                        Infolists\Components\TextEntry::make('screenshot_resolutions')
                            ->label('Screenshot resolutions')
                            ->getStateUsing(function (System $record) {
                                $resolutions = $record->screenshot_resolutions;

                                if (empty($resolutions)) {
                                    return 'Any resolution accepted';
                                }

                                return collect($resolutions)
                                    ->map(fn ($res) => "{$res['width']}x{$res['height']}")
                                    ->join(', ');
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        /** @var User $user */
        $user = Auth::user();

        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Flex::make([
                    Schemas\Components\Section::make()
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->helperText('Used in menus and page titles. May include manufacturer for recognizability.')
                                ->required()
                                ->maxLength(255)
                                ->disabled(!$user->can('updateField', [$schema->model, 'name'])),

                            Forms\Components\TextInput::make('name_short')
                                ->label('Short name')
                                ->helperText('Used in condensed lists and to determine icon image name.')
                                ->maxLength(255)
                                ->disabled(!$user->can('updateField', [$schema->model, 'name_short'])),

                            Forms\Components\TextInput::make('manufacturer')
                                ->helperText('Manufacturer company name.')
                                ->maxLength(255)
                                ->disabled(!$user->can('updateField', [$schema->model, 'manufacturer'])),

                            Forms\Components\TextInput::make('name_full')
                                ->label('Full name')
                                ->helperText('Manufacturer + name. Name might not include manufacturer.')
                                ->maxLength(255)
                                ->disabled(!$user->can('updateField', [$schema->model, 'name_full'])),
                        ]),
                    Schemas\Components\Section::make()
                        ->grow(false)
                        ->schema([
                            Forms\Components\Toggle::make('active')
                                ->disabled(!$user->can('updateField', [$schema->model, 'active'])),
                        ]),
                ])->from('md'),

                Schemas\Components\Section::make('Screenshots')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('has_analog_tv_output')
                            ->label('Has analog TV output')
                            ->helperText('When enabled, SMPTE 601 analog capture resolutions (704x480, 720x480, 720x486, 704x576, 720x576) are accepted in addition to native resolutions. Enable for systems that output to analog TVs/CRTs.')
                            ->disabled(!$user->can('updateField', [$schema->model, 'has_analog_tv_output'])),

                        Forms\Components\Toggle::make('supports_upscaled_screenshots')
                            ->label('Supports upscaled screenshots')
                            ->helperText('When enabled, 2x and 3x integer multiples of native resolutions are accepted. Enable for 3D-capable systems where emulator upscaling is common. Disable for 2D systems that should only accept native resolution.')
                            ->disabled(!$user->can('updateField', [$schema->model, 'supports_upscaled_screenshots'])),

                        Forms\Components\Placeholder::make('screenshot_resolutions_help')
                            ->label('Screenshot resolutions')
                            ->content('Native screen resolutions accepted for screenshot uploads. Leave empty to accept any resolution.')
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('screenshot_resolutions')
                            ->hiddenLabel()
                            ->addActionLabel('Add resolution')
                            ->schema([
                                Forms\Components\TextInput::make('width')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->required(),
                                Forms\Components\TextInput::make('height')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->columnSpanFull()
                            ->reorderable(false)
                            ->grid(2)
                            ->defaultItems(0)
                            ->disabled(!$user->can('updateField', [$schema->model, 'screenshot_resolutions'])),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon_url')
                    ->label('')
                    ->size(config('media.icon.sm.width')),

                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name_full')
                    ->label('Full name')
                    ->description(fn (System $record): ?string => $record->name_short)
                    ->searchable()
                    ->sortable()
                    ->grow(true),

                Tables\Columns\TextColumn::make('name_short')
                    ->label('Short name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('manufacturer')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('name')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('has_analog_tv_output')
                    ->label('Analog TV')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('supports_upscaled_screenshots')
                    ->label('Upscaled')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('screenshot_resolutions')
                    ->label('Resolutions')
                    ->getStateUsing(function (System $record) {
                        $resolutions = $record->screenshot_resolutions;

                        if (empty($resolutions)) {
                            return 'Any';
                        }

                        return collect($resolutions)
                            ->map(fn ($res) => "{$res['width']}x{$res['height']}")
                            ->join(', ');
                    })
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->default(false)
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name_full')
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
                        ->url(fn ($record) => SystemResource::getUrl('audit-log', ['record' => $record]))
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
     * @return Builder<System>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<System> $query */
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        return $query;
    }
}
