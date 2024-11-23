<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\RelationManagers;

use App\Community\Actions\AddGameBadgeCreditAction;
use App\Models\AchievementSetAuthor;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\User;
use App\Platform\Enums\AchievementSetAuthorTask;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CoreSetAuthorshipCreditsRelationManager extends RelationManager
{
    protected static string $relationship = 'coreSetAuthorshipCredits';

    protected static ?string $title = 'Credits';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('task')
                    ->options(
                        collect(AchievementSetAuthorTask::cases())
                            ->mapWithKeys(fn ($enum) => [$enum->value => $enum->label()])
                    )
                    ->helperText('NOTE: This is NOT for achievement badge credit. That credit must be granted at the achievement level, not the game level.')
                    ->required(),

                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        $lowercased = strtolower($search);

                        return User::withTrashed()
                            ->whereRaw('LOWER(User) = ?', [$lowercased])
                            ->orWhere(function ($query) use ($lowercased) {
                                $query->whereRaw('LOWER(display_name) like ?', ["%{$lowercased}%"])
                                    ->orWhereRaw('LOWER(User) like ?', ["%{$lowercased}%"]);
                            })
                            ->orderByRaw('LOWER(User) = ? DESC', [$lowercased])
                            ->limit(50)
                            ->get()
                            ->pluck('display_name', 'id')
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn (int $value): string => User::withTrashed()->find($value)?->display_name ?? 'Deleted User')
                    ->required(),

                Forms\Components\DatePicker::make('created_at')
                    ->label('Date Credited')
                    ->default(now()),
            ]);
    }

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = Auth::user();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.display_name')
                    ->label('User')
                    ->url(fn (AchievementSetAuthor $record): ?string => $record->user ? route('user.show', ['user' => $record->user]) : null),

                Tables\Columns\TextColumn::make('task')
                    ->label('Task')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? ucfirst($state)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date Credited')
                    ->date(),
            ])
            ->filters([

            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add contribution credit')
                    ->modalHeading('Add contribution credit')
                    ->before(function (Tables\Actions\CreateAction $action, array $data) {
                        /** @var Game $game */
                        $game = $this->ownerRecord;

                        $coreAchievementSet = GameAchievementSet::where('game_id', $game->id)
                            ->core()
                            ->first()
                            ->achievementSet;

                        $alreadyExists = AchievementSetAuthor::where('achievement_set_id', $coreAchievementSet->id)
                            ->where('user_id', (int) $data['user_id'])
                            ->where('task', $data['task'])
                            ->exists();

                        if ($alreadyExists) {
                            Notification::make()
                                ->warning()
                                ->title('Duplicate Credit Warning')
                                ->body('This user already has this type of credit for this game.')
                                ->persistent()
                                ->send();

                            $action->halt();
                        }
                    })
                    ->using(function (array $data, string $model): Model {
                        $user = User::withTrashed()->find((int) $data['user_id']);

                        return (new AddGameBadgeCreditAction())->execute(
                            game: $this->ownerRecord,
                            user: $user,
                            date: Carbon::parse($data['created_at']),
                        );
                    })
                    ->visible(fn () => $user->can('addContributionCredit', $this->ownerRecord)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit contribution credit'),

                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Delete contribution credit'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No contribution credits')
            ->emptyStateDescription('Add a contribution credit to see them here.');
    }
}
