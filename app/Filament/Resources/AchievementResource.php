<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\AchievementResource\Pages;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementPoints;
use App\Platform\Enums\AchievementType;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AchievementResource extends Resource
{
    protected static ?string $model = Achievement::class;

    protected static ?string $navigationIcon = 'fas-trophy';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'id_title';

    protected static int $globalSearchResultsLimit = 5;

    /**
     * @param Achievement $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->title;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'ID' => $record->id,
            'Description' => $record->description,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['ID', 'Title', 'Description'];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(1)
            ->schema([
                Infolists\Components\Split::make([
                    Infolists\Components\Section::make()
                        ->columns(['xl' => 2, '2xl' => 3])
                        ->schema([
                            Infolists\Components\Group::make()
                                ->schema([
                                    Infolists\Components\ImageEntry::make('badge_url')
                                        ->label('Badge')
                                        ->size(config('media.icon.lg.width')),
                                    Infolists\Components\ImageEntry::make('badge_locked_url')
                                        ->label('Badge locked')
                                        ->size(config('media.icon.lg.width')),
                                ]),
                            Infolists\Components\Group::make()
                                ->schema([
                                    Infolists\Components\TextEntry::make('Title'),
                                    Infolists\Components\TextEntry::make('Description'),
                                    Infolists\Components\TextEntry::make('game')
                                        ->label('Game')
                                        ->formatStateUsing(fn (Game $state) => '[' . $state->id . '] ' . $state->title),
                                    // ->url(GameResource::getUrl('view', ['record' => $state->id])),
                                ]),
                            Infolists\Components\Group::make()
                                ->schema([
                                    Infolists\Components\TextEntry::make('canonical_url')
                                        ->label('Canonical URL')
                                        ->url(fn (Achievement $record): string => $record->getCanonicalUrlAttribute()),
                                    Infolists\Components\TextEntry::make('permalink')
                                        ->url(fn (Achievement $record): string => $record->getPermalinkAttribute()),
                                ]),
                        ]),
                    Infolists\Components\Section::make([
                        Infolists\Components\TextEntry::make('Created')
                            ->label('Created at')
                            ->dateTime()
                            ->hidden(fn ($state) => !$state),
                        Infolists\Components\TextEntry::make('DateModified')
                            ->label('Modified at')
                            ->dateTime()
                            ->hidden(fn ($state) => !$state),
                        Infolists\Components\TextEntry::make('Updated')
                            ->label('Updated at')
                            ->dateTime()
                            ->hidden(fn ($state) => !$state),
                        Infolists\Components\TextEntry::make('Flags')
                            ->badge()
                            ->formatStateUsing(fn (int $state): string => match ($state) {
                                AchievementFlag::OfficialCore => __('published'),
                                AchievementFlag::Unofficial => __('unpublished'),
                                default => '',
                            })
                            ->color(fn (int $state): string => match ($state) {
                                AchievementFlag::OfficialCore => 'success',
                                AchievementFlag::Unofficial => 'info',
                                default => '',
                            }),
                        Infolists\Components\TextEntry::make('type')
                            ->badge(),
                        Infolists\Components\TextEntry::make('Points'),
                        Infolists\Components\TextEntry::make('DisplayOrder'),
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
                        ->columns(['xl' => 2, '2xl' => 2])
                        ->schema([
                            Forms\Components\TextInput::make('BadgeName')
                                ->required()
                                ->default('00000'),
                            Forms\Components\Group::make()
                                ->schema([
                                    Forms\Components\TextInput::make('Title')
                                        ->required()
                                        ->maxLength(64),
                                    Forms\Components\TextInput::make('Description')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\Select::make('GameID')
                                        ->label('Game')
                                        ->relationship(
                                            name: 'game',
                                            titleAttribute: 'Title',
                                        )
                                        ->searchable(['ID', 'Title'])
                                        ->getOptionLabelFromRecordUsing(fn (Model $record) => "[{$record->ID}] {$record->Title}")
                                        ->required(),
                                ]),
                        ]),
                    Forms\Components\Section::make()
                        ->grow(false)
                        ->schema([
                            Forms\Components\Select::make('Flags')
                                ->options([
                                    AchievementFlag::OfficialCore => __('published'),
                                    AchievementFlag::Unofficial => __('unpublished'),
                                ])
                                ->default(AchievementFlag::Unofficial)
                                ->required(),
                            Forms\Components\Select::make('type')
                                ->options(
                                    collect(AchievementType::cases())
                                        ->mapWithKeys(fn ($value) => [$value => __($value)])
                                ),
                            Forms\Components\Select::make('Points')
                                ->required()
                                ->default(0)
                                ->options(
                                    collect(AchievementPoints::cases())
                                        ->mapWithKeys(fn ($value) => [$value => $value])
                                ),
                            Forms\Components\TextInput::make('DisplayOrder')
                                ->required()
                                ->numeric()
                                ->default(0),
                        ]),
                ])->from('md'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('badge_url')
                    ->label('')
                    ->size(config('media.icon.sm.width')),
                Tables\Columns\TextColumn::make('ID')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('Title')
                    ->label('Achievement')
                    ->wrap()
                    ->description(fn (Achievement $record): string => $record->description)
                    ->searchable(),
                Tables\Columns\TextColumn::make('Description')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('game')
                    ->label('Game')
                    ->formatStateUsing(fn ($state) => '[' . $state->id . '] ' . $state->title),
                    // ->url(GameResource::getUrl('view', ['record' => $state->id])),
                Tables\Columns\TextColumn::make('Flags')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        AchievementFlag::OfficialCore => __('published'),
                        AchievementFlag::Unofficial => __('unpublished'),
                        default => '',
                    })
                    ->color(fn (int $state): string => match ($state) {
                        AchievementFlag::OfficialCore => 'success',
                        AchievementFlag::Unofficial => 'info',
                        default => '',
                    }),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('Points')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('TrueRatio')
                    ->label('RetroPoints')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('unlocks_total')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('unlocks_hardcore_total')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('unlock_percentage')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('unlock_hardcore_percentage')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('BadgeName')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('DisplayOrder')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('DateCreated')
                    ->label('Created at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('DateModified')
                    ->label('Modified at')
                    ->dateTime()
                    ->sortable(),
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
            ->defaultSort('DateModified', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->multiple()
                    ->options(
                        collect(AchievementType::cases())
                            ->mapWithKeys(fn ($value) => [$value => __($value)])
                    ),
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
                        Tables\Actions\ForceDeleteAction::make(),
                    ])->dropdown(false),
                    Tables\Actions\Action::make('audit-log')
                        ->url(fn ($record) => AchievementResource::getUrl('audit-log', ['record' => $record]))
                        ->icon('fas-clock-rotate-left'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\ForceDeleteBulkAction::make(),
                    // Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
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
     * @return Builder<Achievement>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['game']);
    }
}
