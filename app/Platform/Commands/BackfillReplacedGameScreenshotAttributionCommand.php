<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\GameScreenshot;
use App\Models\User;
use App\Platform\Actions\RevalidateMediaContributionBadgeEligibilityAction;
use App\Platform\Enums\GameScreenshotStatus;
use Illuminate\Console\Command;

class BackfillReplacedGameScreenshotAttributionCommand extends Command
{
    protected $signature = 'ra:platform:game-screenshots:backfill-replacement-attribution';
    protected $description = 'Backfill replacement attribution for legacy replaced game screenshots';

    public function handle(RevalidateMediaContributionBadgeEligibilityAction $revalidateBadge): void
    {
        $affectedUserIds = [];

        GameScreenshot::query()
            ->where('status', GameScreenshotStatus::Replaced)
            ->whereNull('replaced_by_user_id')
            ->select(['id', 'game_id', 'type', 'captured_by_user_id'])
            ->eachById(function (GameScreenshot $screenshot) use (&$affectedUserIds): void {
                $successorCapturedByUserId = GameScreenshot::query()
                    ->where('game_id', $screenshot->game_id)
                    ->where('type', $screenshot->type)
                    ->where('id', '>', $screenshot->id)
                    ->whereIn('status', [GameScreenshotStatus::Approved, GameScreenshotStatus::Replaced])
                    ->orderBy('id')
                    ->value('captured_by_user_id');

                if ($successorCapturedByUserId === null) {
                    return;
                }

                $wasUpdated = GameScreenshot::query()
                    ->whereKey($screenshot->id)
                    ->where('status', GameScreenshotStatus::Replaced)
                    ->whereNull('replaced_by_user_id')
                    ->update(['replaced_by_user_id' => $successorCapturedByUserId]);

                if ($wasUpdated !== 1) {
                    return;
                }

                if ($screenshot->captured_by_user_id !== null) {
                    $affectedUserIds[$screenshot->captured_by_user_id] = true;
                }
            });

        if ($affectedUserIds !== []) {
            User::query()
                ->whereKey(array_keys($affectedUserIds))
                ->eachById(function (User $user) use ($revalidateBadge): void {
                    $revalidateBadge->execute($user);
                });
        }
    }
}
