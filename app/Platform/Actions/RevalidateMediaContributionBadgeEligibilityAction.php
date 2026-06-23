<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Models\GameScreenshot;
use App\Models\PlayerBadge;
use App\Models\User;
use App\Platform\Events\SiteBadgeAwarded;

class RevalidateMediaContributionBadgeEligibilityAction
{
    public function execute(User $user): ?PlayerBadge
    {
        $eligibleCount = GameScreenshot::query()
            ->eligibleForMediaContributionBy($user)
            ->count();
        $expectedTier = PlayerBadge::getNewBadgeTier(AwardType::MediaContribution, 0, $eligibleCount);

        $existingBadge = $user->playerBadges()
            ->where('award_type', AwardType::MediaContribution)
            ->first();

        if ($expectedTier === null) {
            // Media contribution badges represent current community screenshot credit.
            // If later dev activity makes those screenshots ineligible, remove the badge.
            $existingBadge?->delete();

            return null;
        }

        if ($existingBadge === null) {
            $newBadge = PlayerBadge::create([
                'user_id' => $user->id,
                'award_type' => AwardType::MediaContribution,
                'award_key' => $expectedTier,
                'award_tier' => $expectedTier,
                'awarded_at' => now(),
                'order_column' => PlayerBadge::getNextDisplayOrder($user),
            ]);

            SiteBadgeAwarded::dispatch($newBadge);

            return $newBadge;
        }

        $currentTier = (int) $existingBadge->award_tier;

        if ($expectedTier === $currentTier) {
            return $existingBadge;
        }

        if ($expectedTier > $currentTier) {
            $existingBadge->award_key = $expectedTier;
            $existingBadge->award_tier = $expectedTier;
            $existingBadge->awarded_at = now();
            $existingBadge->save();

            SiteBadgeAwarded::dispatch($existingBadge);

            return $existingBadge;
        }

        // Downgrade: bump the tier down and leave awarded_at alone.
        $existingBadge->award_key = $expectedTier;
        $existingBadge->award_tier = $expectedTier;

        // Clear the displayed tier only when the user's chosen tier is no longer earned.
        if ($existingBadge->display_award_tier !== null && $existingBadge->display_award_tier > $expectedTier) {
            $existingBadge->display_award_tier = null;
        }

        $existingBadge->save();

        return $existingBadge;
    }
}
