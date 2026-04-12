<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameScreenshot;
use App\Models\User;
use App\Platform\Enums\GameScreenshotRejectionReason;
use App\Platform\Enums\GameScreenshotStatus;

class RejectGameScreenshotAction
{
    public function execute(
        GameScreenshot $screenshot,
        User $reviewer,
        GameScreenshotRejectionReason $reason,
        ?string $notes = null,
    ): void {
        $screenshot->update([
            'status' => GameScreenshotStatus::Rejected,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
            'rejection_notes' => $notes,
        ]);
    }
}
