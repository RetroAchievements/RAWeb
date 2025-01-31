<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\RelationManagers;

use App\Filament\Actions\ProcessUploadedImageAction;
use App\Filament\Enums\ImageUploadType;
use App\Models\Event;
use App\Models\EventAward;
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
    protected static string $relationship = 'awards';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->can('manage', EventAward::class);
    }

    public function form(Form $form): Form
    {
        /** @var Event $event */
        $event = $this->getOwnerRecord();
        $minPoints = 1;
        $maxPoints = $event->achievements()->count();
        $isNew = true;

        if (!$event->awards()->exists()) {
            $tierIndex = 1;
        } else {
            /** @var EventAward $award */
            $award = $form->model;
            if (is_a($award, EventAward::class)) {
                $tierIndex = $award->tier_index;
                $isNew = false;
            } else { // new record just passes the class name as $form->model
                $maxTier = $event->awards()->max('tier_index');
                $tierIndex = $maxTier + 1;
            }

            $previousTier = $event->awards()->where('tier_index', $tierIndex - 1)->first();
            if ($previousTier) {
                $minPoints = $previousTier->points_required + 1;
            }

            $nextTier = $event->awards()->where('tier_index', $tierIndex + 1)->first();
            if ($nextTier) {
                $maxPoints = $nextTier->points_required - 1;
            }
        }

        return $form
            ->schema([
                Forms\Components\TextInput::make('label')
                    ->minLength(2)
                    ->maxLength(40)
                    ->required(),

                Forms\Components\TextInput::make('points_required')
                    ->default($maxPoints)
                    ->numeric()
                    ->minValue($minPoints)
                    ->maxValue($maxPoints)
                    ->required(),

                Forms\Components\TextInput::make('tier_index')
                    ->default($tierIndex)
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
                            ->required($isNew)
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

                Tables\Columns\TextColumn::make('points_required'),
            ])
            ->filters([

            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $this->processUploadedImage($data, null);

                        return $data;
                    })
                    ->createAnother(false), // Create Another doesn't update tier_index, which causes a unique constraint error
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->mutateFormDataUsing(function (Model $record, array $data): array {
                            /** @var EventAward $record */
                            $this->processUploadedImage($data, $record);

                            return $data;
                        }),
                ]),
            ]);
    }

    protected function processUploadedImage(array &$data, ?EventAward $record): void
    {
        if (isset($data['image_asset_path'])) {
            $data['image_asset_path'] = (new ProcessUploadedImageAction())->execute(
                $data['image_asset_path'],
                ImageUploadType::EventAward,
            );
        } else {
            // If no new image was uploaded, retain the existing image.
            unset($data['image_asset_path']);
        }
    }
}
