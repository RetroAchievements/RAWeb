<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\GameScreenshotModerationResource\Pages;
use App\Models\GameScreenshot;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\ApproveGameScreenshotAction;
use App\Platform\Actions\RevalidateMediaContributionBadgeEligibilityAction;
use App\Platform\Enums\GameScreenshotRejectionReason;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotReviewDecision;
use App\Platform\Enums\ScreenshotType;
use App\Platform\Services\ScreenshotReviewContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use UnitEnum;

class GameScreenshotModerationResource extends Resource
{
    protected static ?string $model = GameScreenshot::class;

    protected static ?int $navigationSort = 31;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-s-photo';
    protected static string|UnitEnum|null $navigationGroup = 'Queues';
    protected static ?string $navigationLabel = 'Screenshots';
    protected static ?string $modelLabel = 'Screenshot';
    protected static ?string $pluralModelLabel = 'Screenshots';

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        return (string) self::pendingReviewQueryFor($user)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'warning' : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        /** @var User $user */
        $user = Auth::user();

        return $table
            ->modifyQueryUsing(function (Builder $query) use ($user) {
                /** @var Builder<GameScreenshot> $query */
                self::applyReviewFeedScope($query)
                    ->reviewableBy($user)
                    ->whereHas('game')
                    ->with([
                        'game.system',
                        'capturedBy',
                        'media',
                        'game.gameScreenshots' => fn ($q) => $q
                            ->whereIn('status', [GameScreenshotStatus::Approved, GameScreenshotStatus::Pending])
                            ->with(['media', 'capturedBy']),
                    ]);
            })
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->label('Preview')
                    ->state(fn (GameScreenshot $record) => $record->media?->getUrl())
                    ->width(64)
                    ->imageHeight(48)
                    ->extraImgAttributes(fn (GameScreenshot $record): array => [
                        'class' => $record->status === GameScreenshotStatus::Pending
                            ? 'object-contain bg-gray-950 cursor-pointer'
                            : 'object-contain bg-gray-950',
                    ])
                    ->action(function (GameScreenshot $record, Component $livewire): void {
                        if ($record->status !== GameScreenshotStatus::Pending) {
                            return;
                        }

                        if ($livewire instanceof Pages\Index) {
                            $livewire->replaceMountedScreenshotReview((string) $record->getKey());
                        }
                    }),

                Tables\Columns\TextColumn::make('game.title')
                    ->label('Game')
                    ->formatStateUsing(fn (GameScreenshot $record): HtmlString => self::buildGameColumnContent($record))
                    ->html()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (ScreenshotType $state) => $state->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('capturedBy.display_name')
                    ->label('Submitted By')
                    ->url(fn (GameScreenshot $record) => $record->capturedBy
                        ? route('user.show', ['user' => $record->capturedBy])
                        : null
                    )
                    ->extraAttributes(['class' => '[&_a]:no-underline [&_a:hover]:underline'])
                    ->openUrlInNewTab()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->formatStateUsing(fn ($state): string => $state->diffForHumans(['short' => true]))
                    ->tooltip(fn (GameScreenshot $record): string => $record->created_at->format('Y-m-d H:i:s'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('resolution')
                    ->label('Resolution')
                    ->state(fn (GameScreenshot $record): ?string => ($record->width && $record->height)
                        ? "{$record->width}x{$record->height}"
                        : null
                    )
                    ->formatStateUsing(function (?string $state, GameScreenshot $record): string|HtmlString|null {
                        $mismatches = ScreenshotReviewContext::make($record)->approvedResolutionMismatches();

                        if (
                            $state
                            && !$record->has_wrong_resolution
                            && !empty($mismatches)
                        ) {
                            return self::renderView('table.resolution-warning', [
                                'state' => $state,
                                'icon' => 'heroicon-o-exclamation-triangle',
                            ]);
                        }

                        return $state;
                    })
                    ->html()
                    ->color(fn (GameScreenshot $record): ?string => $record->has_wrong_resolution ? 'danger' : null)
                    ->icon(fn (GameScreenshot $record): ?string => $record->has_wrong_resolution ? 'heroicon-o-exclamation-triangle' : null)
                    ->tooltip(function (GameScreenshot $record): ?string {
                        if ($record->has_wrong_resolution) {
                            return 'Unsupported for this system.';
                        }

                        $mismatches = ScreenshotReviewContext::make($record)->approvedResolutionMismatches();
                        if (!empty($mismatches)) {
                            return self::buildDiffersFromApprovedTooltip($mismatches);
                        }

                        return null;
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (GameScreenshotStatus $state) => match ($state) {
                        GameScreenshotStatus::Approved => 'Approved',
                        GameScreenshotStatus::Pending => 'Pending',
                        GameScreenshotStatus::Rejected => 'Rejected',
                        GameScreenshotStatus::Replaced => 'Replaced',
                    })
                    ->color(fn (GameScreenshotStatus $state) => match ($state) {
                        GameScreenshotStatus::Approved => 'success',
                        GameScreenshotStatus::Pending => 'warning',
                        GameScreenshotStatus::Rejected => 'danger',
                        GameScreenshotStatus::Replaced => 'gray',
                    })
                    ->icon(function (GameScreenshot $record): ?string {
                        if (
                            $record->status === GameScreenshotStatus::Rejected
                            && $record->rejection_notes
                        ) {
                            return 'heroicon-m-information-circle';
                        }

                        return null;
                    })
                    ->iconColor('gray')
                    ->iconPosition('after')
                    ->tooltip(function (GameScreenshot $record): ?HtmlString {
                        if (
                            $record->status !== GameScreenshotStatus::Rejected
                            || !$record->rejection_reason
                        ) {
                            return null;
                        }

                        $lines = [
                            '<div><strong>Reason:</strong> ' . $record->rejection_reason->label() . '</div>',
                        ];

                        if ($record->rejection_notes) {
                            $lines[] = '<div class="mt-1"><strong>Notes:</strong> ' . e($record->rejection_notes) . '</div>';
                        }

                        return new HtmlString(implode('', $lines));
                    })
                    ->visible(fn ($livewire): bool => $livewire instanceof Pages\Index
                        ? self::shouldShowStatusColumn($livewire)
                        : true
                    ),
            ])
            ->defaultSort('created_at', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('system')
                    ->label('System')
                    ->options(function ($livewire) use ($user): array {
                        /** @var array<string, mixed> $statusFilterState */
                        $statusFilterState = $livewire->getTableFilterFormState('status');

                        return self::getSystemFilterOptions(
                            $user,
                            self::resolveStatusFilterValueForSystemOptions($statusFilterState),
                        );
                    })
                    ->query(fn (Builder $query, array $state): Builder => self::applySystemFilter($query, $state['value'] ?? null)),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'title' => 'Title',
                        'ingame' => 'In-game',
                        'completion' => 'Completion',
                    ])
                    ->query(fn (Builder $query, array $state): Builder => filled($state['value'] ?? null)
                        ? $query->where('type', $state['value'])
                        : $query
                    ),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'replaced' => 'Replaced',
                    ])
                    ->query(fn (Builder $query, array $state): Builder => self::applyStatusFilter($query, $state['value'] ?? null))
                    ->default('pending'),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('Review')
                    ->action(function (GameScreenshot $record, array $arguments) use ($user): void {
                        $decision = ScreenshotReviewDecision::from($arguments['decision'] ?? ScreenshotReviewDecision::Primary->value);

                        self::approve($record, $user, $decision);
                    })
                    ->visible(fn (GameScreenshot $record) => $record->status === GameScreenshotStatus::Pending)
                    ->modalIcon(null)
                    ->modalHeading(fn (GameScreenshot $record, Component $livewire): HtmlString => self::buildReviewModalHeader(
                        $record,
                        ScreenshotReviewContext::make($record)->formatResolution(),
                        $user,
                        $livewire instanceof Pages\Index ? $livewire : null,
                    ))
                    ->modalAlignment(Alignment::Start)
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalFooterActions([])
                    ->registerModalActions([
                        self::makeRejectModalAction(),
                        self::makeConfirmReplacePrimaryModalAction(),
                    ])
                    ->modalContent(fn (GameScreenshot $record): View => self::reviewModalContentView($record))
                    ->modalContentFooter(fn (GameScreenshot $record): View => self::reviewModalFooterView($record))
                    ->color('primary')
                    ->icon('heroicon-o-eye'),
            ])
            ->paginated([50])
            ->defaultPaginationPageOption(50)
            ->searchPlaceholder('Search (Game, User)')
            ->toolbarActions([])
            ->emptyStateHeading('No screenshots to review');
    }

    public static function approve(GameScreenshot $record, User $user, ScreenshotReviewDecision $decision): bool
    {
        try {
            (new ApproveGameScreenshotAction())->execute($record, $user, $decision);
            if ($record->capturedBy) {
                (new RevalidateMediaContributionBadgeEligibilityAction())->execute($record->capturedBy);
            }

            return true;
        } catch (ValidationException $e) {
            Notification::make()
                ->danger()
                ->title('Cannot Approve')
                ->body(collect($e->errors())->flatten()->first())
                ->send();

            return false;
        }
    }

    /**
     * @return Builder<GameScreenshot>
     */
    public static function reviewFeedQueryFor(User $user): Builder
    {
        return self::applyReviewFeedScope(GameScreenshot::query())
            ->reviewableBy($user)
            ->whereHas('game');
    }

    /**
     * @return Builder<GameScreenshot>
     */
    public static function pendingReviewQueryFor(User $user): Builder
    {
        return self::reviewFeedQueryFor($user)
            ->where('status', GameScreenshotStatus::Pending);
    }

    /**
     * @param Builder<GameScreenshot> $query
     * @return Builder<GameScreenshot>
     */
    private static function applyStatusFilter(Builder $query, ?string $status): Builder
    {
        $statusEnum = $status !== null ? GameScreenshotStatus::tryFrom($status) : null;

        return $statusEnum ? $query->where('status', $statusEnum) : $query;
    }

    /**
     * @param Builder<GameScreenshot> $query
     * @return Builder<GameScreenshot>
     */
    private static function applySystemFilter(Builder $query, mixed $systemId): Builder
    {
        if (!filled($systemId)) {
            return $query;
        }

        return $query->whereHas('game', fn (Builder $query) => $query->where('system_id', $systemId));
    }

    /**
     * @return array<int, string>
     */
    private static function getSystemFilterOptions(User $user, ?string $status): array
    {
        $systemIds = self::applyStatusFilter(self::reviewFeedQueryFor($user), $status)
            ->join('games', 'games.id', '=', 'game_screenshots.game_id')
            ->distinct()
            ->pluck('games.system_id');

        return System::query()
            ->whereKey($systemIds)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @param array<string, mixed> $statusFilterState
     */
    private static function resolveStatusFilterValueForSystemOptions(array $statusFilterState): ?string
    {
        if (!array_key_exists('value', $statusFilterState)) {
            return GameScreenshotStatus::Pending->value;
        }

        $status = $statusFilterState['value'];

        return is_string($status) && $status !== '' ? $status : null;
    }

    private static function shouldShowStatusColumn(Pages\Index $livewire): bool
    {
        /** @var array<string, mixed> $statusFilterState */
        $statusFilterState = $livewire->getTableFilterFormState('status');

        return ($statusFilterState['value'] ?? null) !== GameScreenshotStatus::Pending->value;
    }

    /**
     * @param Builder<GameScreenshot> $query
     * @return Builder<GameScreenshot>
     */
    public static function applyAdjacentReviewCursor(Builder $query, GameScreenshot $record, bool $previous, bool $reorder = false): Builder
    {
        $operator = $previous ? '<' : '>';

        $query->where(function (Builder $query) use ($record, $operator): void {
            $query
                ->where('created_at', $operator, $record->created_at)
                ->orWhere(function (Builder $query) use ($record, $operator): void {
                    $query
                        ->where('created_at', $record->created_at)
                        ->where('id', $operator, $record->id);
                });
        });

        if ($reorder) {
            $query->reorder();
        }

        return $previous
            ? $query->orderByDesc('created_at')->orderByDesc('id')
            : $query->orderBy('created_at')->orderBy('id');
    }

    private static function makeRejectModalAction(): Action
    {
        return Action::make('rejectScreenshot')
            ->label('Reject')
            ->schema([
                Forms\Components\Select::make('rejection_reason')
                    ->label('Reason')
                    ->options(collect(GameScreenshotRejectionReason::cases())
                        ->mapWithKeys(fn (GameScreenshotRejectionReason $reason) => [
                            $reason->value => $reason === GameScreenshotRejectionReason::InappropriateContent
                                ? $reason->label() . ' (alerts the moderation team)' // don't leak this into user-facing surfaces
                                : $reason->label(),
                        ])
                        ->sortBy(function (string $label, string $value): array {
                            $bucket = match ($value) {
                                GameScreenshotRejectionReason::InappropriateContent->value => 1,
                                GameScreenshotRejectionReason::Other->value => 2,
                                default => 0,
                            };

                            return [$bucket, $label];
                        })
                        ->toArray()
                    )
                    ->default(fn (GameScreenshot $record): ?string => ScreenshotReviewContext::make($record)->suggestedRejectionReason()?->value)
                    ->required(),

                Forms\Components\Textarea::make('rejection_notes')
                    ->label('Notes (optional)')
                    ->maxLength(500),
            ])
            ->modalIcon('heroicon-o-x-mark')
            ->modalHeading('Reject Screenshot')
            ->modalSubmitActionLabel('Reject Screenshot')
            ->modalDescription(fn (GameScreenshot $record): HtmlString => self::buildRejectModalDescription($record))
            ->action(function (GameScreenshot $record, array $data, Component $livewire): void {
                abort_unless($livewire instanceof Pages\Index, 500);

                $livewire->rejectMountedScreenshotReview(
                    recordKey: (string) $record->getKey(),
                    reason: $data['rejection_reason'],
                    notes: $data['rejection_notes'] ?? null,
                );
            })
            ->cancelParentActions('review')
            ->color('danger')
            ->icon('heroicon-o-x-mark');
    }

    private static function makeConfirmReplacePrimaryModalAction(): Action
    {
        return Action::make('confirmReplacePrimaryScreenshot')
            ->label('Replace Primary')
            ->schema([
                Forms\Components\Checkbox::make('keep_current_in_gallery')
                    ->label('Keep the current primary in the gallery')
                    ->helperText('If left unchecked, the current primary will be unpublished and hidden from view.')
                    ->default(false)
                    ->visible(fn (GameScreenshot $record): bool => ScreenshotReviewContext::make($record)->canKeepReplacedPrimaryInGallery()),
            ])
            ->modalIcon('heroicon-o-arrow-path')
            ->modalWidth(Width::Medium)
            ->modalHeading('Replace current primary?')
            ->modalSubmitActionLabel('Replace Primary')
            ->modalDescription(fn (GameScreenshot $record): HtmlString => self::buildReplacePrimaryConfirmationDescription($record))
            ->action(function (GameScreenshot $record, array $data, Component $livewire): void {
                abort_unless($livewire instanceof Pages\Index, 500);

                $decision = ($data['keep_current_in_gallery'] ?? false)
                    ? ScreenshotReviewDecision::PrimaryKeepGallery
                    : ScreenshotReviewDecision::Primary;

                $livewire->approveMountedScreenshotReview((string) $record->getKey(), $decision->value);
            })
            ->cancelParentActions('review')
            ->color('primary')
            ->icon('heroicon-o-arrow-path');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\Index::route('/'),
        ];
    }

    /**
     * Scope the list to user submissions that belong in the review feed.
     *
     * Pending submissions do not have a reviewer yet. Historical rows should represent
     * decisions made by a human reviewer, not automated cleanup or legacy imports.
     *
     * @param Builder<GameScreenshot> $query
     * @return Builder<GameScreenshot>
     */
    private static function applyReviewFeedScope(Builder $query): Builder
    {
        return $query
            ->whereNotNull('captured_by_user_id')
            ->where(function (Builder $query): void {
                $query
                    ->where('status', GameScreenshotStatus::Pending)
                    ->orWhereNotNull('reviewed_by_user_id');
            });
    }

    private static function view(string $name, array $data = []): View
    {
        return view("filament.resources.game-screenshot-moderation-resource.{$name}", $data);
    }

    private static function renderView(string $name, array $data = []): HtmlString
    {
        return new HtmlString(self::view($name, $data)->render());
    }

    private static function buildGameColumnContent(GameScreenshot $record): HtmlString
    {
        return self::renderView('table.game-column', [
            'gameTitle' => $record->game?->title ?? 'Unknown Game',
            'gameUrl' => $record->game ? route('game.show', ['game' => $record->game]) : null,
            'systemName' => $record->game?->system?->name,
            'cues' => self::getReviewCueBadgeData($record),
        ]);
    }

    /**
     * @return array<int, array{label: string, tone: 'danger'|'warning'|'success', title: string, icon: string}>
     */
    private static function getReviewCueBadgeData(GameScreenshot $record): array
    {
        // we already pop a warning in the Resolution column
        if ($record->has_wrong_resolution) {
            return [];
        }

        return collect(ScreenshotReviewContext::make($record)->candidateImageCues())
            ->map(fn (array $cue): array => [
                'label' => $cue['badgeLabel'],
                'tone' => $cue['tone'],
                'title' => $cue['modalLabel'],
                'icon' => self::cueIconFor($cue['tone']),
            ])
            ->all();
    }

    private static function cueIconFor(string $tone): string
    {
        return match ($tone) {
            'success' => 'heroicon-m-check-circle',
            'warning' => 'heroicon-m-exclamation-triangle',
            'danger' => 'heroicon-m-x-circle',
            default => 'heroicon-m-information-circle',
        };
    }

    private static function buildReviewModalHeader(GameScreenshot $record, ?string $submissionResolution, User $user, ?Pages\Index $livewire = null): HtmlString
    {
        $game = $record->game;
        $capturedBy = $record->capturedBy;

        return self::renderView('review-modal.heading', [
            'heading' => 'Review ' . $record->type->label() . ' Screenshot',
            'icon' => 'heroicon-o-photo',
            'gameTitle' => $game?->title ?? 'Unknown Game',
            'gameUrl' => $game ? route('game.show', ['game' => $game]) : null,
            'systemName' => $game?->system?->name,
            'typeLabel' => $record->type->label(),
            'resolution' => $submissionResolution ?? '?',
            'submitterLabel' => $capturedBy?->display_name ?? 'Unknown user',
            'submitterUrl' => $capturedBy ? route('user.show', ['user' => $capturedBy]) : null,
            'submitted' => $record->created_at->diffForHumans(['short' => true]),
            'navigation' => self::getReviewNavigationViewData($record, $user, $livewire),
        ]);
    }

    /**
     * @return array<int, array{label: string, recordKey: string|null, disabled: bool}>
     */
    private static function getReviewNavigationViewData(GameScreenshot $record, User $user, ?Pages\Index $livewire = null): array
    {
        $adjacent = fn (bool $previous): ?GameScreenshot => $livewire
            ? $livewire->getAdjacentFilteredReviewRecord($record, previous: $previous)
            : self::applyAdjacentReviewCursor(self::pendingReviewQueryFor($user), $record, $previous)->first();

        $previous = $adjacent(true);
        $next = $adjacent(false);

        if (!$previous && !$next) {
            return [];
        }

        return [
            [
                'label' => 'Previous',
                'recordKey' => $previous ? (string) $previous->getKey() : null,
                'disabled' => $previous === null,
            ],
            [
                'label' => 'Next',
                'recordKey' => $next ? (string) $next->getKey() : null,
                'disabled' => $next === null,
            ],
        ];
    }

    private static function reviewModalContentView(GameScreenshot $record): View
    {
        return self::view('review-modal.content', self::getReviewModalContentViewData($record));
    }

    private static function reviewModalFooterView(GameScreenshot $record): View
    {
        return self::view('review-modal.footer', [
            'cards' => self::getReviewDecisionCardViewData($record),
            'suggestedPathTooltip' => 'Based on current primary, gallery cap, and resolution. Review image quality before approving.',
        ]);
    }

    /**
     * @return array{
     *     recordKey: string,
     *     isPixelated: bool,
     *     currentPanel: array{label: string, url: string|null, placeholder: string, imageRendering: string|null, cues: array<int, array{label: string, tone: string, icon: string}>},
     *     candidatePanel: array{label: string, url: string|null, placeholder: string, imageRendering: string|null, cues: array<int, array{label: string, tone: string, icon: string}>},
     *     currentPrimaries: array<int, array{typeLabel: string, resolution: string, url: string|null, invalid: bool}>,
     *     allPendingForGame: array{items: array<int, array{recordKey: string, typeLabel: string, resolution: string, submitterLabel: string, url: string|null, isCurrent: bool}>}|null,
     *     approvedIngame: array{count: int, cap: int, mediaPageUrl: string, items: array<int, array{url: string|null, resolution: string, label: string, typeLabel: string, submitterLabel: string, imageRendering: string|null}>}|null,
     * }
     */
    private static function getReviewModalContentViewData(GameScreenshot $record): array
    {
        $context = ScreenshotReviewContext::make($record);
        $record->loadMissing(['capturedBy', 'media']);

        $submissionResolution = $context->formatResolution();
        $currentPrimary = $context->currentPrimaryForType($record->type);
        $currentPrimary?->loadMissing('media');
        $isPixelated = $context->isPixelated();

        return [
            'recordKey' => (string) $record->getKey(),
            'isPixelated' => $isPixelated, // similar to the game page dialog
            'currentPanel' => [
                'label' => $currentPrimary
                    ? 'Current primary (' . $context->formatResolution($currentPrimary) . ')'
                    : 'No current primary',
                'url' => $currentPrimary?->media?->getUrl(),
                'placeholder' => $currentPrimary ? 'No preview' : 'No current image',
                'imageRendering' => $context->imageRenderingFor($currentPrimary),
                'cues' => self::getCurrentImageCueViewData($context, $currentPrimary),
            ],
            'candidatePanel' => [
                'label' => 'Submission (' . ($submissionResolution ?? '?') . ')',
                'url' => $record->media?->getUrl(),
                'placeholder' => 'No preview',
                'imageRendering' => $context->imageRenderingFor($record),
                'cues' => self::getCandidateImageCueViewData($context),
            ],
            'currentPrimaries' => $context->currentPrimaryContextItems(),
            'allPendingForGame' => $context->allPendingForGameContextData(),
            'approvedIngame' => self::getApprovedIngameContextViewData($context),
        ];
    }

    /**
     * @return array<int, array{label: string, tone: string, icon: string}>
     */
    private static function getCurrentImageCueViewData(ScreenshotReviewContext $context, ?GameScreenshot $currentPrimary): array
    {
        if (!$currentPrimary) {
            return [[
                'label' => 'Approving creates this primary',
                'tone' => 'success',
                'icon' => self::cueIconFor('success'),
            ]];
        }

        if ($context->canFixCurrentPrimary()) {
            return [[
                'label' => 'Invalid size',
                'tone' => 'warning',
                'icon' => self::cueIconFor('warning'),
            ]];
        }

        $mismatchCue = $context->currentPrimaryMismatchCueLabels($currentPrimary);
        if ($mismatchCue !== null) {
            return [[
                'label' => $mismatchCue['modalLabel'],
                'tone' => 'warning',
                'icon' => self::cueIconFor('warning'),
            ]];
        }

        if ($context->screenshot()->type !== ScreenshotType::Ingame) {
            return [[
                'label' => 'Current primary will be retired if approved',
                'tone' => 'warning',
                'icon' => self::cueIconFor('warning'),
            ]];
        }

        return [[
            'label' => 'Primary stays visible if added to gallery',
            'tone' => 'neutral',
            'icon' => self::cueIconFor('neutral'),
        ]];
    }

    /**
     * @return array<int, array{label: string, tone: string, icon: string}>
     */
    private static function getCandidateImageCueViewData(ScreenshotReviewContext $context): array
    {
        return collect($context->candidateImageCues())
            ->map(fn (array $cue): array => [
                'label' => $cue['modalLabel'],
                'tone' => $cue['tone'],
                'icon' => self::cueIconFor($cue['tone']),
            ])
            ->all();
    }

    /**
     * @return array{count: int, cap: int, mediaPageUrl: string, items: array<int, array{url: string|null, resolution: string, label: string, typeLabel: string, submitterLabel: string, imageRendering: string|null}>}|null
     */
    private static function getApprovedIngameContextViewData(ScreenshotReviewContext $context): ?array
    {
        $record = $context->screenshot();

        if ($record->type !== ScreenshotType::Ingame) {
            return null;
        }

        return [
            'count' => $context->approvedIngameCount(),
            'cap' => ScreenshotType::Ingame->approvedCap(),
            'mediaPageUrl' => GameResource::getUrl('media', ['record' => $record->game]),
            'items' => $context->approvedGalleryItemsViewData(),
        ];
    }

    /**
     * @return array<int, array{title: string, help: string, detail: string|null, tone: string, icon: string, wireClick: string, recommended: bool}>
     */
    private static function getReviewDecisionCardViewData(GameScreenshot $record): array
    {
        $recordKey = (string) $record->getKey();
        $context = ScreenshotReviewContext::make($record);
        $recommendation = $context->recommendedAction();
        $cards = [];

        $cards[] = [
            'title' => 'Promote to ' . strtolower($record->type->label()) . ' primary',
            'help' => $context->primaryDecisionHelp(),
            'detail' => $context->primaryDecisionDetail(),
            'tone' => 'primary',
            'icon' => 'heroicon-o-star',
            'wireClick' => $context->currentPrimaryForType($record->type) !== null
                ? "mountAction('confirmReplacePrimaryScreenshot')"
                : "approveMountedScreenshotReview('{$recordKey}', '" . ScreenshotReviewDecision::Primary->value . "')",
            'recommended' => $recommendation === ScreenshotReviewDecision::Primary,
        ];

        if ($context->canApproveToGallery()) {
            $cards[] = [
                'title' => 'Add to gallery',
                'help' => 'Approve as a non-primary. Primary stays visible.',
                'detail' => $context->galleryDecisionDetail(),
                'tone' => 'warning',
                'icon' => 'heroicon-o-photo',
                'wireClick' => "approveMountedScreenshotReview('{$recordKey}', '" . ScreenshotReviewDecision::Gallery->value . "')",
                'recommended' => $recommendation === ScreenshotReviewDecision::Gallery,
            ];
        }

        $rejectIsRecommended = $recommendation === ScreenshotReviewDecision::Reject;
        $cards[] = [
            'title' => 'Reject',
            'help' => 'Send back to the submitter with a reason.',
            'detail' => $rejectIsRecommended
                ? 'No other pending screenshot for this game would unify the sizes if you approved one instead.'
                : null,
            'tone' => 'danger',
            'icon' => 'heroicon-o-x-mark',
            'wireClick' => "mountAction('rejectScreenshot')",
            'recommended' => $rejectIsRecommended,
        ];

        return $cards;
    }

    private static function buildReplacePrimaryConfirmationDescription(GameScreenshot $record): HtmlString
    {
        $context = ScreenshotReviewContext::make($record);
        $replacementTarget = $context->currentPrimaryForType($record->type);
        $typeLabel = $record->type->label();
        $currentResolution = $replacementTarget
            ? ($context->formatResolution($replacementTarget) ?? 'unknown resolution')
            : 'unknown resolution';
        $submissionResolution = $context->formatResolution() ?? 'unknown resolution';

        return self::renderView('review-modal.replace-primary-description', [
            'typeLabel' => $typeLabel,
            'currentResolution' => $currentResolution,
            'submissionResolution' => $submissionResolution,
        ]);
    }

    private static function buildRejectModalDescription(GameScreenshot $record): HtmlString
    {
        $gameName = $record->game?->title ?? 'Unknown Game';
        $systemName = $record->game?->system?->name;
        $gameLabel = $systemName ? "{$gameName} ({$systemName})" : $gameName;
        $typeLabel = $record->type->label();
        $submissionUrl = $record->media?->getUrl();
        $submissionResolution = ($record->width && $record->height) ? "{$record->width}x{$record->height}" : null;

        return self::renderView('review-modal.reject-description', [
            'gameLabel' => $gameLabel,
            'typeLabel' => $typeLabel,
            'submissionResolution' => $submissionResolution,
            'submissionUrl' => $submissionUrl,
        ]);
    }

    /**
     * @param array<string, array{count: int, types: array<string, int>}> $mismatches
     */
    public static function buildDiffersFromApprovedTooltip(array $mismatches): string
    {
        $segments = self::buildApprovedResolutionMismatchSegments($mismatches);

        return "Differs from approved: {$segments}";
    }

    /**
     * @param array<string, array{count: int, types: array<string, int>}> $mismatches
     */
    private static function buildApprovedResolutionMismatchSegments(array $mismatches): string
    {
        $typeOrder = collect(ScreenshotType::cases())
            ->mapWithKeys(fn (ScreenshotType $type): array => [$type->label() => $type->sortOrder()])
            ->all();

        $sortKey = fn (array $entry): int => collect($entry['types'])
            ->keys()
            ->map(fn (string $type): int => $typeOrder[$type] ?? 99)
            ->min() ?? 99;

        $segments = collect($mismatches)
            ->sortBy($sortKey)
            ->map(function (array $entry, string $resolution) use ($typeOrder): string {
                $typeBreakdown = collect($entry['types'])
                    ->sortKeysUsing(fn (string $a, string $b): int => ($typeOrder[$a] ?? 99) <=> ($typeOrder[$b] ?? 99))
                    ->map(fn (int $count, string $type): string => "{$count} " . strtolower($type))
                    ->implode(', ');

                return "{$resolution} ({$typeBreakdown})";
            })
            ->implode(', ');

        return $segments;
    }
}
