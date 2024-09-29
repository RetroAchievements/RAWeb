<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementResource\RelationManagers;

use App\Filament\Resources\AchievementAuthorshipCreditFormSchema;
use App\Models\Achievement;
use App\Models\AchievementAuthor;
use App\Platform\Enums\AchievementAuthorTask;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AuthorshipCreditsRelationManager extends RelationManager
{
    protected static string $relationship = 'authorshipCredits';

    protected static ?string $title = 'Credits';

    public function form(Form $form): Form
    {
        return $form
            ->schema(AchievementAuthorshipCreditFormSchema::getSchema());
    }

    public function table(Table $table): Table
    {
        $earliestLogicCredit = AchievementAuthor::where('achievement_id', $this->ownerRecord->id)
            ->where('task', AchievementAuthorTask::Logic->value)
            ->orderBy('created_at', 'asc')
            ->first();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.display_name')
                    ->label('User')
                    ->url(fn (AchievementAuthor $record): ?string => $record->user ? route('user.show', ['user' => $record->user]) : null),

                Tables\Columns\TextColumn::make('task')
                    ->label('Task')
                    ->formatStateUsing(fn ($state) => AchievementAuthorTask::tryFrom($state)?->label() ?? ucfirst($state)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date Credited'),
            ])
            ->filters([

            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add achievement credit')
                    ->modalHeading('Add achievement credit')
                    ->using(function (array $data, string $model): Model {
                        /** @var Achievement $achievement */
                        $achievement = $this->ownerRecord;

                        $task = $data['task'];

                        /**
                         * An achievement developer doing most tasks is implied. If someone
                         * new contributes, we'll backfill a credit record for the achievement
                         * developer.
                         *
                         * For example, if someone creates a new badge for the achievement,
                         * we'll credit the achievement developer for art, then credit the
                         * new person for art.
                         */
                        $developer = $achievement->developer;
                        if ($developer) {
                            $doesDeveloperCreditExist = AchievementAuthor::where('achievement_id', $achievement->id)
                                ->where('user_id', $developer->id)
                                ->where('task', $task)
                                ->exists();

                            if (!$doesDeveloperCreditExist) {
                                $backdate = null;

                                if ($task === AchievementAuthorTask::Artwork->value) {
                                    $firstBadgeComment = $achievement->legacyComments()
                                        ->automated()
                                        ->where('Payload', 'LIKE', "{$developer->display_name}%")
                                        ->where('Payload', 'LIKE', "%badge%")
                                        ->first();

                                    if ($firstBadgeComment) {
                                        $backdate = $firstBadgeComment->Submitted;
                                    }
                                } elseif ($task === AchievementAuthorTask::Writing->value) {
                                    $firstWritingComment = $achievement->legacyComments()
                                        ->automated()
                                        ->where('Payload', 'LIKE', "{$developer->display_name}%")
                                        ->where(function ($query) {
                                            $query->where('Payload', 'LIKE', "%title%")
                                                ->orWhere('Payload', 'LIKE', "%description%");
                                        })
                                        ->first();

                                    if ($firstWritingComment) {
                                        $backdate = $firstWritingComment->Submitted;
                                    }
                                }

                                AchievementAuthor::create([
                                    'user_id' => $developer->id,
                                    'achievement_id' => $achievement->id,
                                    'task' => $task,
                                    'created_at' => $backdate ?? $achievement->DateCreated,
                                ]);
                            }
                        }

                        return AchievementAuthor::create([
                            'user_id' => (int) $data['user_id'],
                            'achievement_id' => $achievement->id,
                            'task' => $task,
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit achievement credit'),

                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Delete achievement credit')
                    ->hidden(fn (AchievementAuthor $record) => $earliestLogicCredit && $earliestLogicCredit->id === $record->id),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
