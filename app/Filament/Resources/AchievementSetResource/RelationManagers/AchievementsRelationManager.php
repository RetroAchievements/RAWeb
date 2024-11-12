<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementSetResource\RelationManagers;

use App\Models\Achievement;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementType;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AchievementsRelationManager extends RelationManager
{
    protected static string $relationship = 'achievements';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->achievements->count();

        return $count > 0 ? "{$count}" : null;
    }

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = Auth::user();

        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\ImageColumn::make('badge_url')
                    ->label('')
                    ->size(config('media.icon.md.width')),

                Tables\Columns\TextColumn::make('title')
                    ->description(fn (Achievement $record): string => $record->description)
                    ->wrap(),

                Tables\Columns\TextColumn::make('type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        AchievementType::Missable => 'Missable',
                        AchievementType::Progression => 'Progression',
                        AchievementType::WinCondition => 'Win Condition',
                        default => '',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        AchievementType::Missable => 'warning',
                        AchievementType::Progression => 'info',
                        AchievementType::WinCondition => 'success',
                        default => '',
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('points')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('DateCreated')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('DateModified')
                    ->date()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('DisplayOrder')
                    ->toggleable(),
            ])
            ->filters([
                Filters\SelectFilter::make('Flags')
                    ->options([
                        0 => 'All',
                        AchievementFlag::OfficialCore->value => __('Published'),
                        AchievementFlag::Unofficial->value => __('Unpublished'),
                    ])
                    ->default(AchievementFlag::OfficialCore->value)
                    ->selectablePlaceholder(false)
                    ->placeholder('All')
                    ->query(function (array $data, Builder $query) {
                        if ((bool) $data['value']) {
                            $query->where('Flags', $data['value']);
                        }
                    }),
            ])
            ->headerActions([

            ])
            ->bulkActions([

            ])
            ->recordUrl(function (Achievement $record) use ($user): string {
                if ($user->can('update', $record)) {
                    return route('filament.admin.resources.achievements.edit', ['record' => $record]);
                }

                return route('filament.admin.resources.achievements.view', ['record' => $record]);
            })
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderBy('DisplayOrder')
                    ->orderBy('DateCreated', 'asc');
            })
            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => $user->can('update', $record->loadMissing('game')),
            );
    }
}
