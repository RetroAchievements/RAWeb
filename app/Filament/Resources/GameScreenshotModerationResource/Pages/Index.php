<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameScreenshotModerationResource\Pages;

use App\Filament\Resources\GameScreenshotModerationResource;
use App\Models\GameScreenshot;
use App\Models\User;
use App\Platform\Actions\RejectGameScreenshotAction;
use App\Platform\Enums\GameScreenshotRejectionReason;
use App\Platform\Enums\ScreenshotReviewDecision;
use Closure;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class Index extends ListRecords
{
    protected static string $resource = GameScreenshotModerationResource::class;

    public bool $mountedScreenshotReviewRespectsFilters = true;
    public ?string $mountedScreenshotReviewRecordKey = null;

    public function replaceMountedScreenshotReview(string $recordKey, bool $respectFilters = false): void
    {
        $this->getPendingReviewQuery(respectFilters: $respectFilters)
            ->whereKey($recordKey)
            ->firstOrFail();

        $this->mountedScreenshotReviewRespectsFilters = $respectFilters;
        $this->mountedScreenshotReviewRecordKey = $recordKey;

        $this->replaceMountedAction('review', context: [
            'table' => true,
            'recordKey' => $recordKey,
        ]);
    }

    public function approveMountedScreenshotReview(string $recordKey, string $kind): void
    {
        $decision = ScreenshotReviewDecision::tryFrom($kind);
        abort_unless($decision !== null, 422, 'Unknown screenshot review decision.');

        /** @var User $user */
        $user = Auth::user();

        $this->performMountedReviewOrAdvance(
            $recordKey,
            $user,
            fn (GameScreenshot $record): bool => GameScreenshotModerationResource::approve(
                $record,
                $user,
                $decision,
            ),
        );
    }

    public function rejectMountedScreenshotReview(string $recordKey, string $reason, ?string $notes = null): void
    {
        $rejectionReason = GameScreenshotRejectionReason::tryFrom($reason);
        abort_unless($rejectionReason !== null, 422, 'Unknown screenshot rejection reason.');

        /** @var User $user */
        $user = Auth::user();

        $this->performMountedReviewOrAdvance(
            $recordKey,
            $user,
            function (GameScreenshot $record) use ($user, $rejectionReason, $notes): bool {
                try {
                    (new RejectGameScreenshotAction())->execute(
                        screenshot: $record,
                        reviewer: $user,
                        reason: $rejectionReason,
                        notes: $notes,
                    );
                } catch (ValidationException $e) {
                    // race condition guard
                    Notification::make()
                        ->danger()
                        ->title('Cannot Reject')
                        ->body(collect($e->errors())->flatten()->first())
                        ->send();

                    return false;
                }

                return true;
            },
        );
    }

    /**
     * @param Closure(GameScreenshot): bool $perform
     */
    private function performMountedReviewOrAdvance(string $recordKey, User $user, Closure $perform): void
    {
        $record = $this->resolvePendingReviewRecord($recordKey, $user);
        if (!$record) {
            $this->advancePastAlreadyReviewedRecord($recordKey, $user);

            return;
        }

        if (!$perform($record)) {
            if (!$this->isRecordStillPendingInCurrentQueue($recordKey)) {
                $this->advancePastAlreadyReviewedRecord($recordKey, $user);
            }

            return;
        }

        $this->advanceMountedReviewOrClose($record);
    }

    private function advanceMountedReviewOrClose(GameScreenshot $record): void
    {
        $nextRecord = $this->getAdjacentFilteredReviewRecord($record, previous: false);

        $this->flushCachedTableRecords();

        if ($nextRecord) {
            $this->replaceMountedScreenshotReview((string) $nextRecord->getKey(), respectFilters: true);

            return;
        }

        $this->unmountAction();
    }

    private function advancePastAlreadyReviewedRecord(string $recordKey, User $user): void
    {
        /** @var GameScreenshot $record */
        $record = GameScreenshotModerationResource::reviewFeedQueryFor($user)
            ->whereKey($recordKey)
            ->firstOrFail();

        Notification::make()
            ->warning()
            ->title('Screenshot Already Reviewed')
            ->body('Another reviewer handled this screenshot first.')
            ->send();

        $this->advanceMountedReviewOrClose($record);
    }

    public function getAdjacentFilteredReviewRecord(GameScreenshot $record, bool $previous): ?GameScreenshot
    {
        /** @var GameScreenshot|null $adjacentRecord */
        $adjacentRecord = GameScreenshotModerationResource::applyAdjacentReviewCursor(
            $this->getPendingReviewQuery(respectFilters: true),
            $record,
            $previous,
            reorder: true,
        )->first();

        return $adjacentRecord;
    }

    /**
     * @return Builder<GameScreenshot>
     */
    private function getPendingReviewQuery(bool $respectFilters): Builder
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$respectFilters) {
            return GameScreenshotModerationResource::pendingReviewQueryFor($user);
        }

        /** @var Builder<GameScreenshot> $query */
        $query = $this->getFilteredTableQuery();
        if (!$query) {
            return GameScreenshotModerationResource::pendingReviewQueryFor($user);
        }

        return $query->whereIn(
            (new GameScreenshot())->qualifyColumn('id'),
            GameScreenshotModerationResource::pendingReviewQueryFor($user)
                ->select((new GameScreenshot())->qualifyColumn('id')),
        );
    }

    private function resolvePendingReviewRecord(string $recordKey, User $user): ?GameScreenshot
    {
        /** @var GameScreenshot|null $record */
        $record = $this->getPendingReviewQuery(respectFilters: $this->mountedScreenshotReviewRespectsFilters)
            ->whereKey($recordKey)
            ->first();

        if ($record) {
            return $record;
        }

        $existsInReviewFeed = GameScreenshotModerationResource::reviewFeedQueryFor($user)
            ->whereKey($recordKey)
            ->exists();

        if ($existsInReviewFeed) {
            return null;
        }

        // we shouldn't ever land here, defense-in-depth
        throw (new ModelNotFoundException())->setModel(GameScreenshot::class, [$recordKey]);
    }

    private function isRecordStillPendingInCurrentQueue(string $recordKey): bool
    {
        return $this->getPendingReviewQuery(respectFilters: $this->mountedScreenshotReviewRespectsFilters)
            ->whereKey($recordKey)
            ->exists();
    }

    /**
     * @return Model|array<string, mixed>|null
     */
    protected function resolveTableRecord(?string $key): Model|array|null
    {
        $record = parent::resolveTableRecord($key);

        if (
            $record
            || $key === null
            || $this->mountedScreenshotReviewRespectsFilters
            || $key !== $this->mountedScreenshotReviewRecordKey
        ) {
            return $record;
        }

        /** @var User $user */
        $user = Auth::user();

        return GameScreenshotModerationResource::pendingReviewQueryFor($user)
            ->whereKey($key)
            ->first();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
