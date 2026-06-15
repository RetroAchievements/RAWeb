<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\SubscriptionSubjectType;
use App\Models\GameScreenshot;
use App\Models\User;
use App\Models\UserDelayedSubscription;
use App\Platform\Enums\GameScreenshotRejectionReason;
use App\Platform\Enums\GameScreenshotStatus;
use App\Support\Alerts\InappropriateGameScreenshotAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectGameScreenshotAction
{
    /**
     * @throws ValidationException
     */
    public function execute(
        GameScreenshot $screenshot,
        ?User $reviewer,
        GameScreenshotRejectionReason $reason,
        ?string $notes = null,
    ): void {
        $screenshot = DB::transaction(function () use ($screenshot, $reviewer, $reason, $notes): GameScreenshot {
            /** @var GameScreenshot $screenshot */
            $screenshot = GameScreenshot::query()
                ->whereKey($screenshot->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($screenshot->status !== GameScreenshotStatus::Pending) {
                throw ValidationException::withMessages([
                    'screenshot' => 'This screenshot has already been reviewed.',
                ]);
            }

            $screenshot->update([
                'status' => GameScreenshotStatus::Rejected,
                'reviewed_by_user_id' => $reviewer?->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
                'rejection_notes' => $notes,
            ]);

            if (
                $screenshot->captured_by_user_id
                && $screenshot->captured_by_user_id !== $reviewer?->id
            ) {
                UserDelayedSubscription::updateOrCreate(
                    [
                        'user_id' => $screenshot->captured_by_user_id,
                        'subject_type' => SubscriptionSubjectType::GameScreenshotDecision,
                        'subject_id' => $screenshot->id,
                    ],
                    [
                        'first_update_id' => $screenshot->id,
                    ],
                );
            }

            return $screenshot;
        }, attempts: 3);

        if ($reason === GameScreenshotRejectionReason::InappropriateContent && $reviewer) {
            $screenshot->loadMissing(['game', 'capturedBy', 'media']);

            (new InappropriateGameScreenshotAlert(
                screenshot: $screenshot,
                reviewer: $reviewer,
            ))->send();
        }
    }
}
