<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementSetResource\RelationManagers;

use App\Models\AchievementSet;
use App\Models\GameHash;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontFamily;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class GameHashesRelationManager extends RelationManager
{
    protected static string $relationship = 'incompatibleGameHashes';

    protected static ?string $title = 'Incompatible Hashes';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->incompatibleGameHashes->count();

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
        /** @var AchievementSet $achievementSet */
        $achievementSet = $this->getOwnerRecord();

        /** @var User $user */
        $user = Auth::user();

        $existingIncompatibleIds = $achievementSet->incompatibleGameHashes()
            ->select('game_hashes.id')
            ->pluck('id')
            ->toArray();

        $availableHashes = $achievementSet
            ->games()
            ->with('hashes')
            ->get()
            ->pluck('hashes')
            ->flatten()
            ->whereNotIn('id', $existingIncompatibleIds)
            ->mapWithKeys(function (GameHash $hash) {
                return [$hash->id => "{$hash->name} {$hash->md5}"];
            })
            ->toArray();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('File Name'),

                Tables\Columns\TextColumn::make('md5')
                    ->label('MD5')
                    ->fontFamily(FontFamily::Mono),
            ])
            ->filters([

            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Remove incompatibility')
                    ->modalHeading('Remove incompatibility')
                    ->modalDescription('Are you sure you want to do this? This hash will once again be compatible with the set.')
                    ->visible(fn () => $user->can('markGameHashAsIncompatible', [AchievementSet::class])),
            ])
            ->headerActions([
                Tables\Actions\Action::make('attachHashCompatibility')
                    ->label('Mark Hash as Incompatible')
                    ->color('danger')
                    ->disabled(empty($availableHashes))
                    ->form([
                        Forms\Components\Placeholder::make('warning')
                            ->label('')
                            ->content(fn () => new HtmlString("
                                    🔴 PLEASE BE CAREFUL. Set types (bonus, specialty, exclusive) should automatically 
                                    handle this for you. Only use this tool if you need to make a rare exception to the rules.
                                    When you press Submit, the hash you've selected will no longer be eligible to receive 
                                    this set's achievements when a player loads the hash in an emulator. This should almost
                                    always be used VERY sparingly. If you find yourself frequently using this tool,
                                    something is wrong. When in doubt, please ask for help!
                                "
                            )),

                        Select::make('hash')
                            ->label('Linked Hash')
                            ->placeholder('Select hash')
                            ->options($availableHashes)
                            ->required(),
                    ])
                    ->action(function (array $data) use ($achievementSet) {
                        $gameHashId = (int) $data['hash'];

                        $achievementSet->incompatibleGameHashes()->attach($gameHashId);

                        Notification::make()
                            ->success()
                            ->title('Success')
                            ->body('Successfully marked hash as incompatible.')
                            ->send();
                    })
                    ->visible(fn () => $user->can('markGameHashAsIncompatible', [AchievementSet::class])),
            ]);
    }

    protected function refreshBadgeCount(Model $ownerRecord): void
    {
        $this->badge = $ownerRecord->incompatibleGameHashes->count();
    }
}
