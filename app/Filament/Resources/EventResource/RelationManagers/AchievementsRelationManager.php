<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\RelationManagers;

use App\Models\EventAchievement;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AchievementsRelationManager extends RelationManager
{
    protected static string $relationship = 'achievements';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = Auth::user();

        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\ImageColumn::make('achievement.badge_url')
                    ->label('')
                    ->size(config('media.icon.md.width')),

                Tables\Columns\TextColumn::make('title')
                    ->description(fn (EventAchievement $record): string => $record->achievement->description)
                    ->wrap(),

                Tables\Columns\TextColumn::make('active_from')
                    ->date()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('active_through')
                    ->date()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('DateCreated')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('DateModified')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('achievement.DisplayOrder')
                    ->label('Display Order')
                    ->toggleable(),
            ])
            ->filters([

            ])
            ->headerActions([

            ])
            ->actions([

            ])
            ->bulkActions([

            ])
            ->recordUrl(function (EventAchievement $record): string {
                /** @var User $user */
                $user = Auth::user();

                if ($user->can('update', $record)) {
                    return route('filament.admin.resources.event-achievements.edit', ['record' => $record]);
                }

                return route('filament.admin.resources.event-achievements.view', ['record' => $record]);
            })
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderBy('DisplayOrder')
                    ->orderBy('DateCreated', 'asc');
            });
    }
}
