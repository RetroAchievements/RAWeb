<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeaderboardResource\RelationManagers;

use App\Filament\Actions\ResetAllLeaderboardEntriesAction;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Platform\Actions\RemoveLeaderboardEntry;
use App\Platform\Enums\ValueFormat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.display_name')
                    ->label('User')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('user', function (Builder $subQuery) use ($search) {
                            return $subQuery->where('display_name', 'LIKE', "%{$search}%")
                                ->orWhere('User', 'LIKE', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('score')
                    ->label('Result')
                    ->formatStateUsing(function (LeaderboardEntry $record) {
                        $record->loadMissing('leaderboard');
                        $format = $record->leaderboard->format;

                        return ValueFormat::format($record->score, $format);
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Date Submitted')
                    ->dateTime(),
            ])
            ->defaultSort('score', function () {
                /** @var Leaderboard $leaderboard */
                $leaderboard = $this->getRelationship()->getParent();

                return $leaderboard->rank_asc ? 'asc' : 'desc';
            })
            ->searchPlaceholder('Search (User)')
            ->filters([

            ])
            ->headerActions([
                ResetAllLeaderboardEntriesAction::make('delete_all_entries'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading(function (LeaderboardEntry $entry): string {
                        $entry->loadMissing('leaderboard');
                        $format = $entry->leaderboard->format;
                        $score = ValueFormat::format($entry->score, $format);

                        return "Delete {$entry->user->display_name}'s entry of {$score}";
                    })
                    ->modalDescription('Are you sure you want to do this? You must provide a reason for the removal.')
                    ->form([
                        Forms\Components\TextInput::make('reason')
                            ->required(),
                    ])
                    ->modalSubmitActionLabel('Remove entry')
                    ->action(function (LeaderboardEntry $entry, array $data) {
                        $reason = $data['reason'];

                        (new RemoveLeaderboardEntry())->execute($entry, $reason);

                        Notification::make()
                            ->title('Success')
                            ->body('Successfully requested entry deletion.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([

            ]);
    }
}
