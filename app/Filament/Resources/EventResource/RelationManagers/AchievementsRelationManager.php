<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\RelationManagers;

use App\Models\Event;
use App\Models\EventAchievement;
use App\Models\User;
use App\Platform\Actions\AddAchievementsToEventAction;
use App\Platform\Jobs\UnlockPlayerAchievementJob;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
                Tables\Actions\Action::make('add-achievements')
                    ->label('Add additional achievements')
                    ->modalHeading('Add additional achievements')
                    ->form([
                        Forms\Components\TextInput::make('numberOfAchievements')
                            ->label('Number of achievements')
                            ->numeric()
                            ->default(1)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $numberOfAchievements = (int) $data['numberOfAchievements'];
                        /** @var Event $event */
                        $event = $this->getOwnerRecord();
                        $user_id = $event->publishedAchievements->first()?->achievement->user_id ?? EventAchievement::RAEVENTS_USER_ID;

                        (new AddAchievementsToEventAction())->execute($event, $numberOfAchievements, $user_id);

                        Notification::make()
                            ->title("Created $numberOfAchievements new " . Str::plural('achievement', $numberOfAchievements))
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => $user->can('manage', Event::class)),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('award')
                        ->label('Award to User(s)')
                        ->icon('fas-trophy')
                        ->form([
                            Forms\Components\Textarea::make('users')
                                ->label('CSV of User names')
                                ->autosize()
                                ->required(),
                        ])
                        ->modalHeading(function (EventAchievement $eventAchievement): string {
                            return "Manually award {$eventAchievement->achievement->title}";
                        })
                        ->action(function (array $data, EventAchievement $eventAchievement): void {
                            /** @var User $unlockedBy */
                            $unlockedBy = auth()->user();

                            $foundCount = 0;
                            $unknown = [];
                            $timestamp = Carbon::now();
                            $lastFoundUser = null;
                            foreach (explode(',', $data['users']) as $username) {
                                $username = trim($username);
                                if (empty($username)) {
                                    continue;
                                }

                                $forUser = User::whereName($username)->first();

                                if ($forUser) {
                                    $foundCount++;
                                    $lastFoundUser = $username;

                                    dispatch(new UnlockPlayerAchievementJob($forUser->id, $eventAchievement->achievement_id, true, $timestamp, $unlockedBy->id))
                                        ->onQueue('player-achievements');
                                } else {
                                    $unknown[] = $username;
                                }
                            }

                            if ($foundCount == 1) {
                                Notification::make()
                                    ->title("Awarded achievement to $lastFoundUser")
                                    ->success()
                                    ->send();
                            } elseif ($foundCount > 0) {
                                Notification::make()
                                    ->title("Awarded achievement to $foundCount users")
                                    ->success()
                                    ->send();
                            }

                            if (!empty($unknown)) {
                                Notification::make()
                                    ->title("Unknown " . Str::plural('user', count($unknown)) . ": " . implode(', ', $unknown))
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->hidden(!$user->can('manage', Event::class)),
                ]),
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
