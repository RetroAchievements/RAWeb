<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\RelationManagers;

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\User;
use App\Platform\Actions\AddGameScreenshotAction;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
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
use Spatie\Activitylog\ActivityLogger;

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
                    ->action(function (array $data) use ($game): void {
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

                        if ($successCount > 0) {
                            $this->logScreenshotActivity($game)
                                ->withProperty('attributes', ['count' => $successCount])
                                ->event('uploadedScreenshots')
                                ->log("Uploaded {$successCount} screenshot(s)");
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
                /** @var Builder<GameScreenshot> $query */
                $query->with(['media', 'game.system'])
                    ->orderByType()
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
                        GameScreenshotStatus::Replaced => 'gray',
                    })
                    ->formatStateUsing(fn (GameScreenshotStatus $state): string => match ($state) {
                        GameScreenshotStatus::Approved => 'Published',
                        default => $state->name,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('resolution')
                    ->label('Resolution')
                    ->state(fn (GameScreenshot $record): ?string => ($record->width && $record->height)
                        ? "{$record->width}x{$record->height}"
                        : null
                    )
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
                    ->action(function (GameScreenshot $record) use ($game): void {
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

                            $record->update([
                                'is_primary' => true,
                                'status' => GameScreenshotStatus::Approved,
                            ]);
                        });

                        $this->logScreenshotActivity($game)
                            ->withProperty('attributes', [
                                'screenshot' => $record->media?->getUrl(),
                                'type' => $record->type->label(),
                            ])
                            ->event('setScreenshotAsPrimary')
                            ->log('Set screenshot as primary');
                    }),

                ActionGroup::make([
                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (GameScreenshot $record): bool => $record->status !== GameScreenshotStatus::Approved)
                        ->action(function (GameScreenshot $record) use ($game): void {
                            $oldStatus = $record->status;

                            $record->update(['status' => GameScreenshotStatus::Approved]);

                            $this->logScreenshotActivity($game)
                                ->withProperty('old', ['status' => $oldStatus->name])
                                ->withProperty('attributes', [
                                    'screenshot' => $record->media?->getUrl(),
                                    'status' => GameScreenshotStatus::Approved->name,
                                ])
                                ->event('approvedScreenshot')
                                ->log('Approved screenshot');
                        }),

                    Action::make('change_type')
                        ->label('Change Type')
                        ->icon('heroicon-o-tag')
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
                        ->action(function (GameScreenshot $record, array $data) use ($game): void {
                            $oldType = $record->type;

                            $record->update(['type' => $data['type']]);

                            $newType = ScreenshotType::from($data['type']);
                            $this->logScreenshotActivity($game)
                                ->withProperty('old', ['type' => $oldType->label()])
                                ->withProperty('attributes', [
                                    'screenshot' => $record->media?->getUrl(),
                                    'type' => $newType->label(),
                                ])
                                ->event('changedScreenshotType')
                                ->log('Changed screenshot type');
                        }),

                    Action::make('reject')
                        ->label(fn (GameScreenshot $record): string => $record->status === GameScreenshotStatus::Approved ? 'Unpublish' : 'Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (GameScreenshot $record): bool => !$record->is_primary && $record->status !== GameScreenshotStatus::Rejected)
                        ->action(function (GameScreenshot $record) use ($game): void {
                            $oldStatus = $record->status;

                            $record->update(['status' => GameScreenshotStatus::Rejected]);

                            $event = $oldStatus === GameScreenshotStatus::Approved
                                ? 'unpublishedScreenshot'
                                : 'rejectedScreenshot';

                            $this->logScreenshotActivity($game)
                                ->withProperty('old', ['status' => $oldStatus->name])
                                ->withProperty('attributes', [
                                    'screenshot' => $record->media?->getUrl(),
                                    'status' => GameScreenshotStatus::Rejected->name,
                                ])
                                ->event($event)
                                ->log($oldStatus === GameScreenshotStatus::Approved ? 'Unpublished screenshot' : 'Rejected screenshot');
                        }),

                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalDescription(fn (GameScreenshot $record): string => $record->is_primary
                            ? 'This is a primary screenshot. The next published screenshot of this type will be promoted automatically, or the placeholder will be restored.'
                            : 'Are you sure you want to delete this screenshot?')
                        ->using(function (GameScreenshot $record) use ($game): void {
                            $screenshotUrl = $record->media?->getUrl();
                            $type = $record->type->label();
                            $wasPrimary = $record->is_primary;

                            $record->media?->delete();
                            $record->delete();

                            $this->logScreenshotActivity($game)
                                ->withProperty('attributes', [
                                    'screenshot' => $screenshotUrl,
                                    'type' => $type,
                                    'was_primary' => $wasPrimary,
                                ])
                                ->event('deletedScreenshot')
                                ->log('Deleted screenshot');
                        }),
                ]),
            ]);
    }

    public function reorderTable(array $order, string|int|null $draggedRecordKey = null): void
    {
        parent::reorderTable($order, $draggedRecordKey);

        $this->logReorderingActivity();
    }

    private function logScreenshotActivity(Game $game): ActivityLogger
    {
        return activity()
            ->useLog('default')
            ->causedBy(Auth::user())
            ->performedOn($game);
    }

    private function logReorderingActivity(): void
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Game $game */
        $game = $this->getOwnerRecord();

        // Throttle reorder events to avoid flooding the audit log.
        $recentReorderingActivity = DB::table('audit_log')
            ->where('causer_id', $user->id)
            ->where('subject_id', $game->id)
            ->where('subject_type', 'game')
            ->where('event', 'reorderedScreenshots')
            ->where('created_at', '>=', now()->subMinutes(10))
            ->first();

        if (!$recentReorderingActivity) {
            $this->logScreenshotActivity($game)
                ->event('reorderedScreenshots')
                ->log('Reordered Screenshots');
        }
    }

    /**
     * @return array<int, string|object>
     */
    private function getScreenshotValidationRules(): array
    {
        $system = $this->getOwnerRecord()?->system;

        return array_filter([
            'dimensions:min_width=64,min_height=64,max_width=3840,max_height=2160',
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

        $multiplesNote = $system->supports_upscaled_screenshots
            ? ' (or 2x/3x integer multiples)'
            : '';

        $text = "{$label} for {$system->name}: {$formatted}{$multiplesNote}";

        if ($system->has_analog_tv_output) {
            $text .= '. SMPTE 601 capture resolutions (704x480, 720x480, 720x486, 704x576, 720x576) are also accepted.';
        }

        return $text;
    }
}
