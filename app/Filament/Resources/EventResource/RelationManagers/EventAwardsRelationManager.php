<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\RelationManagers;

use App\Filament\Actions\ApplyUploadedImageToDataAction;
use App\Filament\Enums\ImageUploadType;
use App\Models\Event;
use App\Models\EventAward;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas;
use Filament\Schemas\Schema;
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

    public function form(Schema $schema): Schema
    {
        /** @var Event $event */
        $event = $this->getOwnerRecord();

        $totalPoints = $event->publishedAchievements()
            ->get()
            ->sum(function ($eventAchievement) {
                return $eventAchievement->achievement->points;
            });

        $defaultTierIndex = ($event->awards()->max('tier_index') ?? 0) + 1;

        return $schema
            ->components([
                Forms\Components\TextInput::make('label')
                    ->minLength(2)
                    ->maxLength(40)
                    ->required(),

                Forms\Components\TextInput::make('points_required')
                    ->default($totalPoints)
                    ->numeric()
                    ->minValue(function (?EventAward $record) use ($event): int {
                        if (!$record) {
                            // For new tiers, points must be higher than the current highest tier.
                            $highestTier = $event->awards()->orderByDesc('tier_index')->first();

                            return $highestTier ? $highestTier->points_required + 1 : 1;
                        }

                        // For existing tiers, respect the previous tier's boundary.
                        $previousTier = $event->awards()->where('tier_index', $record->tier_index - 1)->first();

                        return $previousTier ? $previousTier->points_required + 1 : 1;
                    })
                    ->maxValue(function (?EventAward $record) use ($event, $totalPoints): int {
                        if (!$record) {
                            return $totalPoints;
                        }

                        // For existing tiers, respect the next tier's boundary.
                        $nextTier = $event->awards()->where('tier_index', $record->tier_index + 1)->first();

                        return $nextTier ? $nextTier->points_required - 1 : $totalPoints;
                    })
                    ->required(),

                Forms\Components\TextInput::make('tier_index')
                    ->default($defaultTierIndex)
                    ->numeric()
                    ->readOnly()
                    ->required(),

                Schemas\Components\Section::make('Media')
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
                            ->required(fn (?EventAward $record): bool => $record === null)
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
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $this->processUploadedImage($data, null);

                        return $data;
                    })
                    ->createAnother(false), // Create Another doesn't update tier_index, which causes a unique constraint error
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->mutateDataUsing(function (Model $record, array $data): array {
                            /** @var EventAward $record */
                            $this->processUploadedImage($data, $record);

                            return $data;
                        }),
                ]),
            ]);
    }

    protected function processUploadedImage(array &$data, ?EventAward $record): void
    {
        (new ApplyUploadedImageToDataAction())->execute($data, 'image_asset_path', ImageUploadType::EventAward);
    }
}
