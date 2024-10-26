<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementResource\RelationManagers;

use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\CopyAchievementUnlocksToEventAchievement;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EventAchievementsRelationManager extends RelationManager
{
    protected static string $relationship = 'eventDatas';

    protected static ?string $title = 'Event Achievements';

    public function form(Form $form): Form
    {
        return $form
            ->columns(2)
            ->schema([
                Select::make('source_achievement_id')
                    ->label('Source Achievement')
                    ->columnSpan(2)
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        return Achievement::where('Title', 'like', "%{$search}%")
                            ->orWhere('ID', 'like', "%{$search}%")
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($achievement) {
                                return [$achievement->id => "[{$achievement->id}] {$achievement->title}"];
                            })
                            ->toArray();
                    })
                    ->getOptionLabelUsing(function (int $value): string {
                        $achievement = Achievement::find($value);

                        return "[{$achievement->id}] {$achievement->title}";
                    }),

                Forms\Components\DatePicker::make('active_from')
                    ->label('Active From')
                    ->native(false)
                    ->date(),

                Forms\Components\DatePicker::make('active_through')
                    ->label('Active Through')
                    ->native(false)
                    ->date(),
            ]);
    }

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Achievement $achievement */
        $achievement = $this->getOwnerRecord();

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with('sourceAchievement');
            })
            ->columns([
                Tables\Columns\ImageColumn::make('sourceAchievement.badge_url')
                    ->label('')
                    ->size(config('media.icon.sm.width')),

                Tables\Columns\TextColumn::make('sourceAchievement.title')
                    ->label('Source Achievement')
                    ->wrap(),

                Tables\Columns\TextColumn::make('active_from')
                    ->label('Active From')
                    ->date(),

                Tables\Columns\TextColumn::make('active_through')
                    ->label('Active Through')
                    ->date(),
            ])
            ->filters([

            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->createAnother(false)
                    ->hidden($achievement->eventDatas()->count() > 0)
                    ->after(function (EventAchievement $eventAchievement, Component $livewire) {
                        $eventAchievement->refresh();
                        (new CopyAchievementUnlocksToEventAchievement())->execute($eventAchievement);
                        // TODO: refresh parent achievement data
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->visible(function (EventAchievement $eventAchievement) use ($user) {
                            return $user->can('update', $eventAchievement);
                        })
                        ->after(function (EventAchievement $eventAchievement, Component $livewire) {
                            $eventAchievement->refresh();
                            (new CopyAchievementUnlocksToEventAchievement())->execute($eventAchievement);
                            // TODO: refresh parent achievement data
                        }),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([

            ])
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderBy('active_until', 'desc');
            });
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->game->system->id === System::Events;
    }
}
