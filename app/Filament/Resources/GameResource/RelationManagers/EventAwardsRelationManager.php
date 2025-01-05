<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\RelationManagers;

use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\NewsResource\Actions\ProcessUploadedImageAction;
use App\Models\EventAward;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EventAwardsRelationManager extends RelationManager
{
    protected static string $relationship = 'eventAwards';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if ($ownerRecord->ConsoleID != System::Events) {
            return false;
        }

        /** @var User $user */
        $user = Auth::user();

        return $user->can('manage', EventAward::class);
    }

    public function form(Form $form): Form
    {
        /** @var Game $game */
        $game = $this->getOwnerRecord();
        $nextTier = ($game->eventAwards()->max('tier_index') ?? 0) + 1;

        return $form
            ->schema([
                Forms\Components\TextInput::make('label')
                    ->minLength(2)
                    ->maxLength(40)
                    ->required(),

                Forms\Components\TextInput::make('achievements_required')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('tier_index')
                    ->default($nextTier)
                    ->numeric()
                    ->readOnly()
                    ->required(),

                Forms\Components\Section::make('Media')
                    ->icon('heroicon-s-photo')
                    ->schema([
                        // Store a temporary file on disk until the user submits.
                        // When the user submits, put in storage.
                        Forms\Components\FileUpload::make('image_asset_path')
                            ->label('Badge')
                            ->disk('livewire-tmp') // Use Livewire's self-cleaning temporary disk
                            ->image()
                            ->rules([
                                'dimensions:width=96,height=96',
                            ])
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->previewable(true),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                Tables\Columns\TextColumn::make('tier_index'),

                Tables\Columns\ImageColumn::make('badgeUrl')
                    ->label('Badge')
                    ->size(config('media.icon.md.width')),

                Tables\Columns\TextColumn::make('label')
                    ->label('Label'),

                Tables\Columns\TextColumn::make('achievements_required'),
            ])
            ->filters([

            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $this->processUploadedImage($data, null);

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->mutateFormDataUsing(function (Model $record, array $data): array {
                            /** @var EventAward $record */
                            $this->processUploadedImage($data, $record);

                            return $data;
                        }),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ]);
    }

    protected function processUploadedImage(array &$data, ?EventAward $record): void
    {
        $existingImage = $record->image_asset_path ?? '/Images/000001.png';

        if (isset($data['image_asset_path'])) {
            $data['image_asset_path'] = (new ProcessUploadedImageAction())->execute(
                $data['image_asset_path'],
                ImageUploadType::EventAward,
            );
        } else {
            // If no new image was uploaded, retain the existing image.
            $data['image_asset_path'] = $existingImage;
        }
    }
}
