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

        $existingBadges = $user->playerBadges()
            ->where('award_type', AwardType::MediaContribution)
            ->orderByDesc('award_key')
            ->get();

        if ($expectedTier === null) {
            // Media contribution badges represent current community screenshot credit.
            // If later dev activity makes those screenshots ineligible, remove the badge.
            if ($existingBadges->isNotEmpty()) {
                PlayerBadge::whereKey($existingBadges->modelKeys())->delete();
            }

            return null;
        }

        $previousHighestBadge = $existingBadges->first();

        // If only some screenshots stopped counting, keep the earned tier that still
        // matches current eligibility and remove any higher tiers.
        $tooHighIds = $existingBadges
            ->where('award_key', '>', $expectedTier)
            ->modelKeys();
        if ($tooHighIds) {
            PlayerBadge::whereKey($tooHighIds)->delete();
        }

        $expectedBadge = $existingBadges->first(
            fn (PlayerBadge $badge) => $badge->award_key === $expectedTier && $badge->award_tier === 0,
        );
        if ($expectedBadge) {
            return $expectedBadge;
        }

        $newBadge = AddSiteAward(
            user: $user,
            awardType: AwardType::MediaContribution,
            data: $expectedTier,
            displayOrder: $previousHighestBadge?->order_column ?? PlayerBadge::getNextDisplayOrder($user),
        );

        if ($previousHighestBadge === null || $newBadge->award_key > $previousHighestBadge->award_key) {
            SiteBadgeAwarded::dispatch($newBadge);
        }

        return $newBadge;
    }
}
