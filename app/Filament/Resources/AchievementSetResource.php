<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\AchievementSetResource\Pages;
use App\Filament\Resources\AchievementSetResource\RelationManagers\AchievementsRelationManager;
use App\Filament\Resources\AchievementSetResource\RelationManagers\GameAchievementSetsRelationManager;
use App\Filament\Resources\AchievementSetResource\RelationManagers\GameHashesRelationManager;
use App\Models\AchievementSet;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class AchievementSetResource extends Resource
{
    protected static ?string $model = AchievementSet::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Sets';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 52;

    protected static bool $shouldRegisterNavigation = false;

    protected static bool $isGloballySearchable = false;

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
