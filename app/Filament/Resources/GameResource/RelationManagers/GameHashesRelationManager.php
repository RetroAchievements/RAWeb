<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\RelationManagers;

use App\Community\Enums\ArticleType;
use App\Models\GameHash;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontFamily;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class GameHashesRelationManager extends RelationManager
{
    protected static string $relationship = 'hashes';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->can('manage', GameHash::class);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('File Name'),

                Tables\Columns\TextColumn::make('md5')
                    ->label('MD5')
                    ->fontFamily(FontFamily::Mono),

                Tables\Columns\TextColumn::make('labels')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('patch_url')
                    ->label('RAPatches URL')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('source')
                    ->label('Resource Page URL')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

            ])
            ->headerActions([

            ])
            ->actions([
                Tables\Actions\EditAction::make()
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
                                    ]),

                                Forms\Components\TextInput::make('patch_url')
                                    ->label('RAPatches URL')
                                    ->placeholder('https://github.com/RetroAchievements/RAPatches/raw/main/NES/Subset/5136-CastlevaniaIIBonus.zip')
                                    ->helperText('This MUST be a URL to a .zip or .7z file in the RAPatches GitHub repo, eg: https://github.com/RetroAchievements/RAPatches/raw/main/NES/Subset/5136-CastlevaniaIIBonus.zip'),   // TODO url pattern validation

                                Forms\Components\TextInput::make('source')
                                    ->label('Resource Page URL')
                                    ->helperText('Do not link to a commercially-sold ROM. Link to a specific No Intro, Redump, RHDN, SMWCentral, itch.io, etc. page.'),   // TODO url validation
                            ]),
                    ]),

                Tables\Actions\Action::make('unlink')
                    ->label('Unlink hash')
                    ->icon('heroicon-s-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription("ARE YOU ABSOLUTELY SURE you want to unlink the hash from this game? This can cause a lot of tickets if you don't know what you're doing.")
                    ->action(function (GameHash $gameHash) {
                        /** @var User $user */
                        $user = auth()->user();

                        if (!$user->can('forceDelete', $gameHash)) {
                            return;
                        }

                        $gameId = $gameHash->game_id;
                        $hash = $gameHash->md5;

                        $gameHash->forceDelete();

                        // TODO eventually remove this
                        addArticleComment(
                            "Server",
                            ArticleType::GameHash,
                            $gameId,
                            "{$hash} unlinked by {$user->display_name}"
                        );
                    })
                    ->visible(function (GameHash $gameHash): bool {
                        /** @var User $user */
                        $user = auth()->user();

                        return $user->can('forceDelete', $gameHash);
                    }),
            ]);
    }
}
