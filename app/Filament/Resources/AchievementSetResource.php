<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\AchievementSetResource\Pages;
use App\Filament\Resources\AchievementSetResource\RelationManagers\AchievementsRelationManager;
use App\Filament\Resources\AchievementSetResource\RelationManagers\GameAchievementSetsRelationManager;
use App\Filament\Resources\AchievementSetResource\RelationManagers\GameHashesRelationManager;
use App\Models\AchievementSet;
use App\Models\GameAchievementSet;
use App\Platform\Enums\AchievementSetType;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AchievementSetResource extends Resource
{
    protected static ?string $model = AchievementSet::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Sets';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 52;

    protected static ?string $recordTitleAttribute = 'title';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Primary Details')
                    ->icon('heroicon-m-key')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),
                    ]),

                Infolists\Components\Section::make('Metrics')
                    ->icon('heroicon-s-arrow-trending-up')
                    ->description("
                        Statistics regarding the set's players and achievements can be found here.
                    ")
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Infolists\Components\Fieldset::make('Players')
                            ->schema([
                                Infolists\Components\TextEntry::make('players_total')
                                    ->label('Total')
                                    ->numeric(),

                                Infolists\Components\TextEntry::make('players_hardcore')
                                    ->label('Hardcore')
                                    ->numeric(),
                            ])
                            ->columns(2)
                            ->columnSpan(['md' => 2, 'xl' => 1, '2xl' => 1]),

                        Infolists\Components\Fieldset::make('Achievements')
                            ->schema([
                                Infolists\Components\TextEntry::make('achievements_published')
                                    ->label('Published')
                                    ->numeric(),

                                Infolists\Components\TextEntry::make('achievements_unpublished')
                                    ->label('Unofficial')
                                    ->numeric(),
                            ])
                            ->columns(2)
                            ->columnSpan(['md' => 2, 'xl' => 1, '2xl' => 1]),

                        Infolists\Components\Fieldset::make('Score')
                            ->schema([
                                Infolists\Components\TextEntry::make('points_total')
                                    ->label('Points')
                                    ->numeric(),

                                Infolists\Components\TextEntry::make('points_weighted')
                                    ->label('RetroPoints')
                                    ->numeric(),
                            ])
                            ->columns(2)
                            ->columnSpan(['md' => 2, 'xl' => 1, '2xl' => 1]),
                    ]),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('game_achievement_sets_count')
                    ->label('Links')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('game_achievement_sets_game_titles')
                    ->label('Linked To <(GameID:Title) [Set Name] - Set Type>')
                    ->listWithLineBreaks()
                    ->getStateUsing(function (AchievementSet $record) {
                        $mapped = $record->gameAchievementSets
                            ->map(function (GameAchievementSet $gameAchievementSet) {
                                $setType = $gameAchievementSet->type->label();
                                $gameTitle = "({$gameAchievementSet->game->id}:{$gameAchievementSet->game->title})";
                                $gameAchievementSetTitle = $gameAchievementSet->type !== AchievementSetType::Core
                                    ? "[{$gameAchievementSet->title}]"
                                    : "";

                                return [
                                    'setType' => $setType,
                                    'gameTitle' => $gameTitle,
                                    'display' => "{$gameTitle} {$gameAchievementSetTitle} - {$setType}",
                                ];
                            });

                        // If any game title contains "[Subset -", flip the sort order so the subset's
                        // "core" set appears on the bottom of the list items.
                        $containsSubset = $mapped->contains(fn ($item) => str_contains($item['gameTitle'], '[Subset -'));
                        if ($containsSubset) {
                            return $mapped->reverse()->pluck('display');
                        }

                        // Otherwise, return the normal mapped list.
                        return $mapped->pluck('display');
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        // Search by game ID, game title, or set title.
                        return $query->whereHas('gameAchievementSets.game', function (Builder $gameQuery) use ($search) {
                            $gameQuery->where('GameData.ID', 'LIKE', "%{$search}%")
                                ->orWhere('GameData.Title', 'LIKE', "%{$search}%")
                                ->orWhereHas('gameAchievementSets', function (Builder $setQuery) use ($search) {
                                    $setQuery->where('title', 'LIKE', "%{$search}%");
                                });
                        });
                    }),

                Tables\Columns\TextColumn::make('players_total')
                    ->label('Players (Total)')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('players_hardcore')
                    ->label('Players (Hardcore)')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('achievements_published')
                    ->label('Achievements (Published)')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('achievements_unpublished')
                    ->label('Achievements (Unofficial)')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('points_total')
                ->label('Points')
                ->numeric()
                ->sortable()
                ->alignEnd()
                ->toggleable(),

                Tables\Columns\TextColumn::make('points_weighted')
                    ->label('RetroPoints')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([

            ])
            ->searchPlaceholder('Search (Game ID, Title)');
    }

    public static function getRelations(): array
    {
        return [
            AchievementsRelationManager::class,
            GameAchievementSetsRelationManager::class,
            GameHashesRelationManager::class,
        ];
    }

    // TODO we should probably eventually support this for privileged users
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationitems([
            Pages\Details::class,
            Pages\AuditLog::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\Index::route('/'),
            'view' => Pages\Details::route('/{record}'),
            'audit-log' => Pages\AuditLog::route('/{record}/audit-log'),
        ];
    }

    /**
     * @return Builder<AchievementSet>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('gameAchievementSets')
            ->with(['gameAchievementSets.game.system']);
    }
}
