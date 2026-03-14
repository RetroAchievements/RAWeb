<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\RelationManagers;

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\User;
use App\Platform\Actions\AddGameScreenshotAction;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use App\Platform\Services\ScreenshotResolutionService;
use App\Rules\DisallowAnimatedImageRule;
use App\Rules\ValidScreenshotResolutionRule;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GameScreenshotsRelationManager extends RelationManager
{
    protected static string $relationship = 'gameScreenshots';
    protected static ?string $recordTitleAttribute = 'id';
    protected static string|BackedEnum|null $icon = 'heroicon-s-camera';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->can('updateField', [$ownerRecord, 'screenshots']);
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        /** @var Game $game */
        $game = $ownerRecord;

        $count = $game->gameScreenshots()->approved()->count();

        return $count > 0 ? "{$count}" : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        /** @var Game $game */
        $game = $this->getOwnerRecord();
        $system = $game->system;
        $resolutionService = new ScreenshotResolutionService();

        return $table
            ->headerActions([
                Action::make('upload_screenshot')
                    ->label('Upload Screenshots')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->schema([
                        Forms\Components\FileUpload::make('screenshot_upload')
                            ->label('Screenshots')
                            ->disk('livewire-tmp')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->panelLayout('grid')
                            ->maxFiles(20)
                            ->maxSize(4096)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->nestedRecursiveRules($this->getScreenshotValidationRules())
                            ->previewable(true)
                            ->helperText($this->getScreenshotHelperText()),
                    ])
                    ->action(function (array $data): void {
                        /** @var Game $game */
                        $game = $this->getOwnerRecord();

                        $uploads = $data['screenshot_upload'] ?? [];

                        if (empty($uploads)) {
                            return;
                        }

                        $addAction = new AddGameScreenshotAction();
                        $failureMessages = [];
                        $successCount = 0;

                        foreach ($uploads as $upload) {
                            $filePath = storage_path('app/livewire-tmp/' . $upload);
                            if (!file_exists($filePath)) {
                                continue;
                            }

                            $uploadedFile = new UploadedFile($filePath, basename($filePath), test: true);

                            try {
                                $addAction->execute($game, $uploadedFile, ScreenshotType::Ingame, isPrimary: false);
                                $successCount++;
                            } catch (ValidationException $e) {
                                $failureMessages[] = collect($e->errors())->flatten()->first();
                            }
                        }

                        if (!empty($failureMessages)) {
                            $title = $successCount > 0
                                ? "{$successCount} uploaded, some failed"
                                : 'No screenshots were uploaded';

                            Notification::make()
                                ->warning()
                                ->title($title)
                                ->body(implode("\n", array_unique($failureMessages)))
                                ->send();

                            return;
                        }

                        $label = $successCount === 1 ? 'Screenshot' : "{$successCount} screenshots";
                        Notification::make()
                            ->success()
                            ->title("{$label} uploaded successfully")
                            ->send();
                    }),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $query->with('media')
                    ->orderByRaw("FIELD(type, ?, ?, ?)", [
                        ScreenshotType::Title->value,
                        ScreenshotType::Ingame->value,
                        ScreenshotType::Completion->value,
                    ])
                    ->orderBy('order_column');
            })
            ->reorderRecordsTriggerAction(
                fn (Action $action, bool $isReordering) => $action
                    ->button()
                    ->label($isReordering ? 'Done dragging' : 'Drag to reorder'),
            )
            ->reorderable('order_column')
            ->paginated(false)
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->label('Preview')
                    ->getStateUsing(function (GameScreenshot $record): ?string {
                        $media = $record->media;
                        if (!$media) {
                            return null;
                        }

                        // Fall back to the original when conversions
                        // are still processing after a fresh upload,
                        // otherwise we see a broken image.
                        return $media->hasGeneratedConversion('sm-webp')
                            ? $media->getUrl('sm-webp')
                            : $media->getUrl();
                    })
                    ->imageWidth(64)
                    ->imageHeight(48)
                    ->url(fn (GameScreenshot $record): ?string => $record->media?->getUrl())
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('type')
                    ->formatStateUsing(fn (ScreenshotType $state): string => $state->label())
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (GameScreenshotStatus $state): string => match ($state) {
                        GameScreenshotStatus::Approved => 'success',
                        GameScreenshotStatus::Pending => 'warning',
                        GameScreenshotStatus::Rejected => 'danger',
                    })
                    ->formatStateUsing(fn (GameScreenshotStatus $state): string => match ($state) {
                        GameScreenshotStatus::Approved => 'Published',
                        default => $state->name,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('resolution')
                    ->label('Resolution')
                    ->getStateUsing(function (GameScreenshot $record) use ($system, $resolutionService): ?string {
                        if (!$record->width || !$record->height) {
                            return null;
                        }

                        // Stash validity so the color/icon closures
                        // don't need to recompute it per row.
                        $record->setAttribute('has_wrong_resolution',
                            !empty($system?->screenshot_resolutions)
                            && !$resolutionService->isValidResolution($record->width, $record->height, $system)
                        );

                        return "{$record->width}x{$record->height}";
                    })
                    ->color(fn (GameScreenshot $record): ?string => $record->has_wrong_resolution ? 'danger' : null)
                    ->icon(fn (GameScreenshot $record): ?string => $record->has_wrong_resolution ? 'heroicon-o-exclamation-triangle' : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(collect(ScreenshotType::cases())
                        ->mapWithKeys(fn (ScreenshotType $type) => [$type->value => $type->label()])
                        ->toArray()),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        GameScreenshotStatus::Approved->value => 'Published',
                        GameScreenshotStatus::Pending->value => 'Pending',
                        GameScreenshotStatus::Rejected->value => 'Rejected',
                    ]),

            ])
            ->emptyStateHeading('No screenshots yet')
            ->emptyStateDescription('Upload screenshots using the button above.')
            ->emptyStateIcon('heroicon-o-camera')
            ->recordActions([
                Action::make('set_as_primary')
                    ->label('Set as Primary')
                    ->icon('heroicon-o-star')
                    ->iconButton()
                    ->tooltip('Set as Primary')
                    ->requiresConfirmation()
                    ->hidden(fn (GameScreenshot $record): bool => $record->is_primary)
                    ->action(function (GameScreenshot $record): void {
                        DB::transaction(function () use ($record) {
                            // Demote the current primary of the same type via Eloquent
                            // so model events fire if the observer ever needs to react.
                            $currentPrimary = GameScreenshot::where('game_id', $record->game_id)
                                ->where('type', $record->type)
                                ->where('is_primary', true)
                                ->lockForUpdate()
                                ->first();

                            if ($currentPrimary) {
                                $currentPrimary->update(['is_primary' => false]);
                            }

                            // Promote this record and auto-approve if pending.
                            $record->update([
                                'is_primary' => true,
                                'status' => GameScreenshotStatus::Approved,
                            ]);
                        });
                    }),

                ActionGroup::make([
                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (GameScreenshot $record): bool => $record->status !== GameScreenshotStatus::Approved)
                        ->action(function (GameScreenshot $record): void {
                            $record->update(['status' => GameScreenshotStatus::Approved]);
                        }),

                    Action::make('change_type')
                        ->label('Change Type')
                        ->icon('heroicon-o-tag')
                        ->hidden(fn (GameScreenshot $record): bool => $record->is_primary)
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->label('Type')
                                ->options(collect(ScreenshotType::cases())
                                    ->mapWithKeys(fn (ScreenshotType $type) => [$type->value => $type->label()])
                                    ->toArray())
                                ->default(fn (GameScreenshot $record): string => $record->type->value)
                                ->required()
                                ->native(false)
                                ->helperText('Title: title screen or main menu. In-game: normal gameplay. Completion: ending, credits, or 100% screen.'),
                        ])
                        ->action(function (GameScreenshot $record, array $data): void {
                            $record->update(['type' => $data['type']]);
                        }),

                    Action::make('reject')
                        ->label(fn (GameScreenshot $record): string => $record->status === GameScreenshotStatus::Approved ? 'Unpublish' : 'Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (GameScreenshot $record): bool => !$record->is_primary && $record->status !== GameScreenshotStatus::Rejected)
                        ->action(function (GameScreenshot $record): void {
                            $record->update(['status' => GameScreenshotStatus::Rejected]);
                        }),

                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalDescription(fn (GameScreenshot $record): string => $record->is_primary
                            ? 'This is a primary screenshot. The next published screenshot of this type will be promoted automatically, or the placeholder will be restored.'
                            : 'Are you sure you want to delete this screenshot?')
                        ->using(function (GameScreenshot $record): void {
                            // Delete the associated Spatie Media record (handles S3 cleanup).
                            $record->media?->delete();

                            // Delete the GameScreenshot record (observer handles promotion/placeholder).
                            $record->delete();
                        }),
                ]),
            ]);
    }

    /**
     * @return array<int, string|object>
     */
    private function getScreenshotValidationRules(): array
    {
        $system = $this->getOwnerRecord()?->system;

        return array_filter([
            'dimensions:min_width=64,min_height=64,max_width=1920,max_height=1080',
            new DisallowAnimatedImageRule(),
            $system ? new ValidScreenshotResolutionRule($system) : null,
        ]);
    }

    private function getScreenshotHelperText(): ?string
    {
        $system = $this->getOwnerRecord()?->system;
        $resolutions = $system?->screenshot_resolutions;
        if (empty($resolutions)) {
            return null;
        }

        $formatted = collect($resolutions)
            ->map(fn (array $r) => "{$r['width']}x{$r['height']}")
            ->join(', ');

        $label = count($resolutions) > 1 ? 'Accepted resolutions' : 'Expected resolution';

        $text = "{$label} for {$system->name}: {$formatted} (or 2x/3x integer multiples where dimensions permit)";

        if ($system->has_analog_tv_output) {
            $text .= '. SMPTE 601 capture resolutions (704x480, 720x480, 720x486, 704x576, 720x576) are also accepted.';
        }

        return $text;
    }
}
