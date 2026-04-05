<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteAwardResource\RelationManagers;

use App\Community\Enums\AwardType;
use App\Models\AchievementSetAuthor;
use App\Models\PlayerBadge;
use App\Models\SiteAward;
use App\Models\User;
use App\Platform\Enums\AchievementSetAuthorTask;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AwardedUsersRelationManager extends RelationManager
{
    protected static string $relationship = 'playerBadges';
    protected static ?string $title = 'Awarded Users';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->can('manage', SiteAward::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.display_name')
                    ->label('User')
                    ->url(fn (PlayerBadge $record): ?string => $record->user ? route('user.show', ['user' => $record->user]) : null),

                Tables\Columns\TextColumn::make('awarded_at')
                    ->label('Awarded')
                    ->dateTime(),
            ])
            ->headerActions([
                Action::make('assignAward')
                    ->label('Assign award')
                    ->modalHeading('Assign award')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search): array {
                                return User::search($search)
                                    ->take(50)
                                    ->get()
                                    ->pluck('display_name', 'id')
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn (int $value): string => User::find($value)?->display_name ?? 'Unknown User')
                            ->required(),
                    ])
                    ->action(function (array $data, Action $action) {
                        $userId = (int) $data['user_id'];

                        /** @var SiteAward $siteAward */
                        $siteAward = $this->ownerRecord;
                        $awardType = $siteAward->award_type;

                        // Playtest awards require playtesting credit.
                        if ($awardType === AwardType::Playtest) {
                            $hasPlaytestingCredit = AchievementSetAuthor::where('user_id', $userId)
                                ->where('task', AchievementSetAuthorTask::Testing)
                                ->exists();

                            if (!$hasPlaytestingCredit) {
                                Notification::make()
                                    ->warning()
                                    ->title('No Playtesting Credit')
                                    ->body('This user has no playtesting credit and cannot receive a playtest award.')
                                    ->persistent()
                                    ->send();

                                $action->halt(); // throw a client-side exception
                            }
                        }

                        // Check for duplicate before entering the transaction.
                        $existingAward = PlayerBadge::where('user_id', $userId)
                            ->where('award_type', $awardType)
                            ->first();

                        if ($existingAward && (int) $existingAward->award_key === $siteAward->id) {
                            Notification::make()
                                ->warning()
                                ->title('Already Awarded')
                                ->body('This user already has this award.')
                                ->persistent()
                                ->send();

                            $action->halt(); // throw a client-side exception
                        }

                        // A user can only have one award per type at a time. Replace any existing one.
                        DB::transaction(function () use ($userId, $siteAward, $awardType, $existingAward) {
                            $orderColumn = null;

                            if ($existingAward) {
                                $orderColumn = $existingAward->order_column;
                                $existingAward->delete();
                            }

                            PlayerBadge::create([
                                'user_id' => $userId,
                                'award_type' => $awardType,
                                'award_key' => $siteAward->id,
                                'award_tier' => 0,
                                'awarded_at' => now(),
                                'order_column' => $orderColumn ?? ((PlayerBadge::where('user_id', $userId)->max('order_column') ?? 0) + 1),
                            ]);
                        });

                        Notification::make()
                            ->success()
                            ->title('Award assigned')
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('Revoke')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading('Revoke award')
                    ->action(fn (PlayerBadge $record) => $record->delete()),
            ])
            ->emptyStateHeading('No users awarded')
            ->emptyStateDescription('Assign this award to a user to see them here.');
    }
}
