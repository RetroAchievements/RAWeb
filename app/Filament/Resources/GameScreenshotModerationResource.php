<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\GameScreenshotModerationResource\Pages;
use App\Models\GameScreenshot;
use App\Models\User;
use App\Platform\Actions\ApproveGameScreenshotAction;
use App\Platform\Actions\RejectGameScreenshotAction;
use App\Platform\Enums\GameScreenshotRejectionReason;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use App\Platform\Services\ScreenshotResolutionService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
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

        return (string) GameScreenshot::reviewableBy($user)
            ->where('status', GameScreenshotStatus::Pending)
            ->count();
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
                $query
                    ->reviewableBy($user)
                    ->whereHas('game')
                    ->with(['game.system', 'capturedBy', 'media']);
            })
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->label('Preview')
                    ->state(fn (GameScreenshot $record) => $record->media?->getUrl())
                    ->width(64)
                    ->imageHeight(48)
                    ->url(fn (GameScreenshot $record) => $record->media?->getUrl())
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('game.title')
                    ->label('Game')
                    ->description(fn (GameScreenshot $record) => $record->game?->system?->name)
                    ->url(fn (GameScreenshot $record) => $record->game
                        ? route('game.show', ['game' => $record->game])
                        : null
                    )
                    ->extraAttributes(['class' => 'hover:underline'])
                    ->openUrlInNewTab()
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
                    ->extraAttributes(['class' => 'underline'])
                    ->openUrlInNewTab()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('resolution')
                    ->label('Resolution')
                    ->state(fn (GameScreenshot $record): ?string => ($record->width && $record->height)
                        ? "{$record->width}x{$record->height}"
                        : null
                    )
                    ->color(fn (GameScreenshot $record): ?string => $record->has_wrong_resolution ? 'danger' : null)
                    ->icon(fn (GameScreenshot $record): ?string => $record->has_wrong_resolution ? 'heroicon-o-exclamation-triangle' : null)
                    ->tooltip(fn (GameScreenshot $record): ?string => $record->has_wrong_resolution ? 'Unsupported for this system.' : null),

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
                            $lines[] = '<div style="margin-top: 0.25rem;"><strong>Notes:</strong> ' . e($record->rejection_notes) . '</div>';
                        }

                        return new HtmlString(implode('', $lines));
                    }),
            ])
            ->defaultSort('created_at', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'replaced' => 'Replaced',
                    ])
                    ->query(function ($query, $state) {
                        if (!isset($state['value'])) {
                            return $query;
                        }

                        return match ($state['value']) {
                            'pending' => $query->where('status', GameScreenshotStatus::Pending),
                            'approved' => $query->where('status', GameScreenshotStatus::Approved),
                            'rejected' => $query->where('status', GameScreenshotStatus::Rejected),
                            'replaced' => $query->where('status', GameScreenshotStatus::Replaced),
                            default => $query,
                        };
                    })
                    ->default('pending'),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'title' => 'Title',
                        'ingame' => 'In-game',
                        'completion' => 'Completion',
                    ])
                    ->query(function ($query, $state) {
                        if (!isset($state['value'])) {
                            return $query;
                        }

                        return $query->where('type', $state['value']);
                    }),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(fn (GameScreenshot $record) => self::getApproveLabel($record))
                    ->action(function (GameScreenshot $record) use ($user) {
                        try {
                            (new ApproveGameScreenshotAction())->execute($record, $user);

                            Notification::make()
                                ->success()
                                ->title('Screenshot Approved')
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot Approve')
                                ->body(collect($e->errors())->flatten()->first())
                                ->send();
                        }
                    })
                    ->visible(fn (GameScreenshot $record) => $record->status === GameScreenshotStatus::Pending)
                    ->requiresConfirmation()
                    ->modalIcon('heroicon-s-photo')
                    ->modalHeading(fn (GameScreenshot $record) => self::getApproveHeading($record))
                    ->modalSubmitActionLabel(fn (GameScreenshot $record) => self::getApproveLabel($record))
                    ->modalSubmitAction(function (Action $action, GameScreenshot $record) {
                        if (
                            $record->type === ScreenshotType::Ingame
                            && $record->game->gameScreenshots()->ofType(ScreenshotType::Ingame)->approved()->count() >= 20
                        ) {
                            return $action->hidden();
                        }

                        return $action;
                    })
                    ->modalDescription(fn (GameScreenshot $record): HtmlString => self::buildApproveModalDescription($record))
                    ->color('success')
                    ->icon('heroicon-o-check'),

                Action::make('reject')
                    ->schema([
                        Forms\Components\Select::make('rejection_reason')
                            ->label('Reason')
                            ->options(collect(GameScreenshotRejectionReason::cases())
                                ->mapWithKeys(fn (GameScreenshotRejectionReason $reason) => [$reason->value => $reason->label()])
                                ->toArray()
                            )
                            ->required(),

                        Forms\Components\Textarea::make('rejection_notes')
                            ->label('Notes (optional)')
                            ->maxLength(500),
                    ])
                    ->modalIcon('heroicon-s-photo')
                    ->modalHeading('Reject Screenshot')
                    ->modalSubmitActionLabel('Reject Screenshot')
                    ->modalFooterActionsAlignment('end')
                    ->modalFooterActions(fn (Action $action): array => array_values(array_filter([
                        $action->getModalSubmitAction(),
                        $action->getModalCancelAction(),
                    ])))
                    ->modalDescription(function (GameScreenshot $record): HtmlString {
                        $gameName = $record->game?->title;
                        $systemName = $record->game?->system?->name;
                        $gameLabel = $systemName ? "{$gameName} ({$systemName})" : $gameName;
                        $typeLabel = $record->type->label();
                        $submissionUrl = $record->media?->getUrl();
                        $submissionResolution = ($record->width && $record->height) ? "{$record->width}x{$record->height}" : null;

                        $submissionPreview = $submissionUrl
                            ? <<<HTML
                                <a href="{$submissionUrl}" target="_blank" style="display: block; flex: none;">
                                    <img src="{$submissionUrl}" style="display: block; width: 132px; max-width: 132px; height: auto; border-radius: 0.25rem; cursor: pointer;" />
                                </a>
                                HTML
                            : '';
                        $submissionMeta = $submissionResolution ? " · {$submissionResolution}" : '';

                        return new HtmlString(<<<HTML
                            <div style="display: flex; gap: 1rem; align-items: flex-start; margin-top: 0.75rem;">
                                {$submissionPreview}
                                <div style="min-width: 0;">
                                    <p style="font-weight: 600; margin: 0;">{$gameLabel}</p>
                                    <p style="margin: 0.35rem 0 0; color: #9ca3af;">{$typeLabel} submission{$submissionMeta}</p>
                                </div>
                            </div>
                            HTML);
                    })
                    ->action(function (GameScreenshot $record, array $data) use ($user) {
                        (new RejectGameScreenshotAction())->execute(
                            screenshot: $record,
                            reviewer: $user,
                            reason: GameScreenshotRejectionReason::from($data['rejection_reason']),
                            notes: $data['rejection_notes'] ?? null,
                        );

                        Notification::make()
                            ->success()
                            ->title('Screenshot Rejected')
                            ->send();
                    })
                    ->visible(fn (GameScreenshot $record) => $record->status === GameScreenshotStatus::Pending)
                    ->color('danger')
                    ->icon('heroicon-o-x-mark'),
            ])
            ->paginated([50])
            ->defaultPaginationPageOption(50)
            ->searchPlaceholder('Search (Game, User)')
            ->toolbarActions([])
            ->emptyStateHeading('No screenshots to review');
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

    private static function buildApproveModalDescription(GameScreenshot $record): HtmlString
    {
        $submissionUrl = $record->media?->getUrl();
        $submissionResolution = ($record->width && $record->height) ? "{$record->width}x{$record->height}" : null;
        $subjectLine = self::buildApproveSubjectLine($record);
        $mixedResolutionWarning = self::buildMixedPrimaryResolutionWarning($record);

        if ($record->type === ScreenshotType::Ingame) {
            return self::buildApproveIngameDescription(
                record: $record,
                subjectLine: $subjectLine,
                submissionUrl: $submissionUrl,
                submissionResolution: $submissionResolution,
                mixedResolutionWarning: $mixedResolutionWarning,
            );
        }

        return self::buildApprovePrimaryDescription(
            record: $record,
            subjectLine: $subjectLine,
            submissionUrl: $submissionUrl,
            submissionResolution: $submissionResolution,
            mixedResolutionWarning: $mixedResolutionWarning,
        );
    }

    private static function buildApproveIngameDescription(
        GameScreenshot $record,
        string $subjectLine,
        ?string $submissionUrl,
        ?string $submissionResolution,
        string $mixedResolutionWarning,
    ): HtmlString {
        $approvedCount = $record->game->gameScreenshots()
            ->ofType(ScreenshotType::Ingame)
            ->approved()
            ->count();

        $countLabel = $approvedCount === 1 ? 'screenshot' : 'screenshots';
        $mediaPageUrl = GameResource::getUrl('media', ['record' => $record->game]);

        if ($approvedCount >= 20) {
            return new HtmlString(
                $subjectLine
                . self::buildApproveExplanation('This game already has 20 in-game screenshots approved.&nbsp;(20&nbsp;max)')
                . self::buildApproveExplanation("To approve this submission, first remove a screenshot from the <a href=\"{$mediaPageUrl}\" target=\"_blank\" style=\"text-decoration: underline;\">game's media page</a>.")
                . self::buildCenteredPreview(self::buildApproveImageTag($submissionUrl, $submissionResolution, ''))
            );
        }

        if (self::willReplaceIngamePrimary($record)) {
            $existingPrimary = $record->game->gameScreenshots()
                ->ofType(ScreenshotType::Ingame)
                ->approved()
                ->primary()
                ->with('media')
                ->first();

            $currentResolution = "{$existingPrimary->width}x{$existingPrimary->height}";
            $currentResolutionLabel = "<span style=\"color: #ef4444;\">⚠ {$currentResolution} (invalid)</span>";

            return new HtmlString(
                $subjectLine
                . self::buildApproveExplanation('This will replace the current primary in-game screenshot (invalid resolution) and add it to the gallery.')
                . self::buildApproveExplanation("{$approvedCount} in-game {$countLabel} currently approved.&nbsp;(20&nbsp;max)")
                . $mixedResolutionWarning
                . self::buildComparisonPreview(
                    currentLabel: 'Current Primary (' . $currentResolutionLabel . ')',
                    currentHtml: self::buildApproveImageTag($existingPrimary->media?->getUrl()),
                    submissionLabel: 'New Submission (' . ($submissionResolution ?? '?') . ')',
                    submissionHtml: self::buildApproveImageTag($submissionUrl),
                )
            );
        }

        return new HtmlString(
            $subjectLine
            . self::buildApproveExplanation("This will add the screenshot to the game's gallery.")
            . self::buildApproveExplanation("{$approvedCount} in-game {$countLabel} currently approved.&nbsp;(20&nbsp;max)")
            . self::buildCenteredPreview(self::buildApproveImageTag($submissionUrl, $submissionResolution, ''))
        );
    }

    private static function buildApprovePrimaryDescription(
        GameScreenshot $record,
        string $subjectLine,
        ?string $submissionUrl,
        ?string $submissionResolution,
        string $mixedResolutionWarning,
    ): HtmlString {
        $existing = $record->game->gameScreenshots()
            ->ofType($record->type)
            ->approved()
            ->with('media')
            ->first();

        $typeLabel = $record->type->label();

        if (!$existing) {
            return new HtmlString(
                $subjectLine
                . self::buildApproveExplanation("This game does not currently have a {$typeLabel} screenshot.")
                . $mixedResolutionWarning
                . self::buildCenteredPreview(self::buildApproveImageTag($submissionUrl, $submissionResolution, ''))
            );
        }

        $resolutionService = new ScreenshotResolutionService();
        $system = $record->game?->system;

        $currentResolution = "{$existing->width}x{$existing->height}";
        $hasResolutionIssue = $system
            && !empty($system->screenshot_resolutions)
            && !$resolutionService->isValidResolution($existing->width, $existing->height, $system);
        $currentResolutionLabel = $hasResolutionIssue
            ? "<span style=\"color: #ef4444;\">⚠ {$currentResolution} (invalid)</span>"
            : $currentResolution;

        return new HtmlString(
            $subjectLine
            . $mixedResolutionWarning
            . self::buildComparisonPreview(
                currentLabel: 'Current (' . $currentResolutionLabel . ')',
                currentHtml: self::buildApproveImageTag($existing->media?->getUrl()),
                submissionLabel: 'New Submission (' . ($submissionResolution ?? '?') . ')',
                submissionHtml: self::buildApproveImageTag($submissionUrl),
            )
        );
    }

    private static function buildApproveSubjectLine(GameScreenshot $record): string
    {
        $gameName = $record->game?->title;
        $systemName = $record->game?->system?->name;
        $gameLabel = $systemName ? "{$gameName} ({$systemName})" : $gameName;

        return '<p style="font-weight: 600; margin: 0.75rem 0 0;">' . $gameLabel . '</p>';
    }

    private static function buildApproveExplanation(string $text): string
    {
        return '<p style="margin: 0.5rem 0 0;">' . $text . '</p>';
    }

    private static function buildApproveImageTag(
        ?string $url,
        ?string $resolution = null,
        string $fallback = '<em>No preview</em>',
    ): string {
        if (!$url) {
            return $fallback;
        }

        $resolutionMarkup = $resolution
            ? <<<HTML
                <div style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem;">{$resolution}</div>
                HTML
            : '';

        return <<<HTML
            <div style="display: flex; flex-direction: column; align-items: center;">
                <a href="{$url}" target="_blank"><img src="{$url}" style="max-width: 100%; max-height: 200px; border-radius: 0.25rem; cursor: pointer;" /></a>
                {$resolutionMarkup}
            </div>
            HTML;
    }

    private static function buildCenteredPreview(string $previewHtml): string
    {
        return <<<HTML
            <div style="display: flex; justify-content: center; margin-top: 1rem;">
                {$previewHtml}
            </div>
            HTML;
    }

    private static function buildComparisonPreview(
        string $currentLabel,
        string $currentHtml,
        string $submissionLabel,
        string $submissionHtml,
    ): string {
        return <<<HTML
            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <div style="flex: 1; text-align: center;">
                    <div style="font-size: 0.75rem; margin-bottom: 0.25rem; color: #9ca3af;">{$currentLabel}</div>
                    {$currentHtml}
                </div>
                <div style="flex: 1; text-align: center;">
                    <div style="font-size: 0.75rem; margin-bottom: 0.25rem; color: #9ca3af;">{$submissionLabel}</div>
                    {$submissionHtml}
                </div>
            </div>
            HTML;
    }

    /**
     * @return array{message: string, resolutions: array<string, string>}|null
     */
    public static function getMixedPrimaryResolutionWarningData(GameScreenshot $record): ?array
    {
        if (!$record->width || !$record->height) {
            return null;
        }

        $system = $record->game?->system;
        if (!$system || empty($system->screenshot_resolutions)) {
            return null;
        }

        if (
            $record->type === ScreenshotType::Ingame
            && !self::willReplaceIngamePrimary($record)
        ) {
            return null;
        }

        $resolutionService = new ScreenshotResolutionService();

        if (!$resolutionService->isValidResolution($record->width, $record->height, $system)) {
            return null;
        }

        $primaryResolutions = [
            $record->type->label() => "{$record->width}x{$record->height}",
        ];

        $otherPrimaries = $record->game->gameScreenshots()
            ->where('id', '!=', $record->id)
            ->where('type', '!=', $record->type)
            ->approved()
            ->primary()
            ->whereNotNull('width')
            ->whereNotNull('height')
            ->get();

        foreach ($otherPrimaries as $otherPrimary) {
            if (!$resolutionService->isValidResolution($otherPrimary->width, $otherPrimary->height, $system)) {
                continue;
            }

            $primaryResolutions[$otherPrimary->type->label()] = "{$otherPrimary->width}x{$otherPrimary->height}";
        }

        if (count($primaryResolutions) < 2 || count(array_unique($primaryResolutions)) < 2) {
            return null;
        }

        return [
            'message' => 'Primary screenshots will no longer match in size.',
            'resolutions' => $primaryResolutions,
        ];
    }

    private static function buildMixedPrimaryResolutionWarning(GameScreenshot $record): string
    {
        $warningData = self::getMixedPrimaryResolutionWarningData($record);
        if (!$warningData) {
            return '';
        }

        $resolutionOrder = [
            ScreenshotType::Title->label() => 0,
            ScreenshotType::Ingame->label() => 1,
            ScreenshotType::Completion->label() => 2,
        ];

        $resolutionLines = collect($warningData['resolutions'])
            ->sortKeysUsing(fn (string $a, string $b): int => ($resolutionOrder[$a] ?? 99) <=> ($resolutionOrder[$b] ?? 99))
            ->map(fn (string $resolution, string $label): string => '<li><strong>' . $label . ':</strong> ' . $resolution . '</li>')
            ->implode('');

        $message = $warningData['message'];

        return <<<HTML
            <div style="margin-top: 0.75rem; padding: 0.625rem 0.75rem; border-radius: 0.5rem; background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.22);">
                <div style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #fcd34d;">Warning</div>
                <div style="margin-top: 0.2rem; font-weight: 600; color: #f9fafb;">{$message}</div>
                <ul style="margin: 0.375rem 0 0 1rem; padding: 0; color: #d1d5db;">{$resolutionLines}</ul>
            </div>
            HTML;
    }

    private static function willReplaceIngamePrimary(GameScreenshot $record): bool
    {
        if ($record->type !== ScreenshotType::Ingame) {
            return false;
        }

        $existingPrimary = $record->game->gameScreenshots()
            ->ofType(ScreenshotType::Ingame)
            ->approved()
            ->primary()
            ->first();

        if (!$existingPrimary) {
            return false;
        }

        $system = $record->game?->system;
        if (!$system || empty($system->screenshot_resolutions)) {
            return false;
        }

        $resolutionService = new ScreenshotResolutionService();

        $primaryHasInvalidResolution = !$resolutionService->isValidResolution(
            $existingPrimary->width,
            $existingPrimary->height,
            $system,
        );

        $newHasValidResolution = $resolutionService->isValidResolution(
            $record->width,
            $record->height,
            $system,
        );

        return $primaryHasInvalidResolution && $newHasValidResolution;
    }

    private static function getApproveLabel(GameScreenshot $record): string
    {
        if ($record->type === ScreenshotType::Ingame) {
            return self::willReplaceIngamePrimary($record)
                ? 'Set as Primary'
                : 'Add to Gallery';
        }

        $existing = $record->game->gameScreenshots()
            ->ofType($record->type)
            ->approved()
            ->exists();

        return $existing ? 'Replace Current' : 'Use Screenshot';
    }

    private static function getApproveHeading(GameScreenshot $record): string
    {
        if ($record->type === ScreenshotType::Ingame) {
            return self::getApproveLabel($record);
        }

        $typeLabel = $record->type->label();
        $existing = $record->game->gameScreenshots()
            ->ofType($record->type)
            ->approved()
            ->exists();

        return $existing
            ? "Replace {$typeLabel} Screenshot"
            : "Set as {$typeLabel} Screenshot";
    }
}
