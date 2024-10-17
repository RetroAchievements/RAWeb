<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementSetResource\RelationManagers;

use App\Filament\Resources\GameResource;
use App\Models\GameAchievementSet;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class GameAchievementSetsRelationManager extends RelationManager
{
    protected static string $relationship = 'gameAchievementSets';

    protected static ?string $title = 'Links';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->gameAchievementSets->count();

        return $count > 0 ? "{$count}" : null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('game.id')
                    ->label('Game ID'),

                Tables\Columns\TextColumn::make('game.title')
                    ->label('Game Title'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Set Name')
                    ->placeholder('Core Set'),
            ])
            ->recordUrl(fn (GameAchievementSet $record) => GameResource::getUrl('view', ['record' => $record->game]));
    }
}
