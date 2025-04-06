<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Pages;

use App\Community\Enums\ArticleType;
use App\Enums\GameHashCompatibility;
use App\Filament\Resources\GameHashResource;
use App\Filament\Resources\GameResource;
use App\Models\Comment;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Support\Enums\FontFamily;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Livewire;

class Hashes extends ManageRelatedRecords
{
    protected static string $resource = GameResource::class;

    protected static string $relationship = 'hashes';

    protected static ?string $navigationIcon = 'fas-file-archive';

    public static function canAccess(array $arguments = []): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->can('manage', GameHash::class);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Livewire::current()->getRecord()->hashes->count();
    }

    public function table(Table $table): Table
    {
        // TODO migrate to filament-comments
        $nonAutomatedCommentsCount = Comment::where('ArticleType', ArticleType::GameHash)
            ->where('ArticleID', $this->getOwnerRecord()->id)
            ->notAutomated()
            ->count();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('File Name')
                    ->sortable(),

                Tables\Columns\TextColumn::make('md5')
                    ->label('MD5')
                    ->fontFamily(FontFamily::Mono)
                    ->sortable(),

                Tables\Columns\TextColumn::make('compatibility')
                    ->label('Compatibility')
                    ->formatStateUsing(function (string $state): string
                    {
                        return GameHashCompatibility::from($state)->label();
                    }),

                Tables\Columns\TextColumn::make('labels')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('patch_url')
                    ->label('RAPatches URL')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('source')
                    ->label('Resource Page URL')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([

            ])
            ->headerActions([
                Tables\Actions\Action::make('view-comments')
                    ->color($nonAutomatedCommentsCount > 0 ? 'info' : 'gray')
                    ->label("View Comments ({$nonAutomatedCommentsCount})")
                    ->url(route('game.hashes.comment.index', ['game' => $this->getOwnerRecord()->id])),
            ])
            ->actions([
                Tables\Actions\Action::make('audit-log')
                    ->url(fn ($record) => GameHashResource::getUrl('audit-log', ['record' => $record]))
                    ->icon('fas-clock-rotate-left'),

                Tables\Actions\EditAction::make()
                    ->modalHeading(fn (GameHash $record) => "Edit game hash {$record->md5}")
                    ->form([
                        Forms\Components\Section::make()
                            ->description("
                                If you're not 100% sure of what you're doing, contact RAdmin and they'll help you out.
                            ")
                            ->icon('heroicon-c-exclamation-triangle')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->columns(['xl' => 2])
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('File Name')
                                            ->required(),

                                        Forms\Components\TextInput::make('labels'),

                                        Forms\Components\Select::make('compatibility')
                                            ->options([
                                                GameHashCompatibility::Compatible->value => GameHashCompatibility::Compatible->label(),
                                                GameHashCompatibility::Incompatible->value => GameHashCompatibility::Incompatible->label(),
                                                GameHashCompatibility::Untested->value => GameHashCompatibility::Untested->label(),
                                                GameHashCompatibility::PatchRequired->value => GameHashCompatibility::PatchRequired->label(),
                                            ]),
                                    ]),

                                Forms\Components\TextInput::make('patch_url')
                                    ->label('Patch URL')
                                    ->placeholder('https://github.com/RetroAchievements/RAPatches/raw/main/NES/Subset/5136-CastlevaniaIIBonus.zip')
                                    ->helperText('This MUST be a URL to a .zip or .7z file in the RAPatches GitHub repo, eg: https://github.com/RetroAchievements/RAPatches/raw/main/NES/Subset/5136-CastlevaniaIIBonus.zip')
                                    ->regex('/^https:\/\/github\.com\/RetroAchievements\/RAPatches\/raw\/(?:refs\/heads\/)?main\/.*\.(zip|7z)$/i'),

                                Forms\Components\TextInput::make('source')
                                    ->label('Resource Page URL')
                                    ->helperText('Do not link to a commercially-sold ROM. Link to a specific No Intro, Redump, RHDN, SMWCentral, itch.io, etc. page.')
                                    ->activeUrl(),
                            ])
                            ->afterStateUpdated(function ($state, $old, $record) {
                                $changedAttributes = [];
                                foreach ($state as $key => $value) {
                                    if (!isset($old[$key]) || $old[$key] !== $value) {
                                        $key = match ($key) {
                                            'name' => 'Name',
                                            'labels' => 'Labels',
                                            default => $key,
                                        };
                                        $changedAttributes[$key] = $value;
                                    }
                                }
                            }),
                    ]),

                Tables\Actions\Action::make('unlink')
                    ->label('Unlink hash')
                    ->icon('heroicon-s-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription("ARE YOU ABSOLUTELY SURE you want to unlink the hash from this game? This can cause a lot of tickets if you don't know what you're doing.")
                    ->action(function (GameHash $gameHash) {
                        /** @var User $user */
                        $user = Auth::user();
                        /** @var Game $game */
                        $game = $gameHash->game;

                        if (!$user->can('forceDelete', $gameHash)) {
                            return;
                        }

                        $gameId = $gameHash->game_id;
                        $md5 = $gameHash->md5;
                        $name = $gameHash->name;
                        $labels = $gameHash->labels;

                        $gameHash->forceDelete();

                        activity()
                            ->useLog('default')
                            ->causedBy($user)
                            ->performedOn($game)
                            ->withProperties([
                                'attributes' => [
                                    'name' => '',
                                    'md5' => '',
                                    'labels' => '',
                                ],
                                'old' => [
                                    'name' => $name,
                                    'md5' => $md5,
                                    'labels' => $labels,
                                ],
                            ])
                            ->event('unlinkedHash')
                            ->log('Unlinked hash');

                        addArticleComment(
                            "Server",
                            ArticleType::GameHash,
                            $gameId,
                            "{$md5} unlinked by {$user->display_name}"
                        );
                    })
                    ->visible(function (GameHash $gameHash): bool {
                        /** @var User $user */
                        $user = Auth::user();

                        return $user->can('forceDelete', $gameHash);
                    }),
            ])
            ->bulkActions([

            ])
            ->paginated(false);
    }
}
