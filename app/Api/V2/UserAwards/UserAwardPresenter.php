<?php

declare(strict_types=1);

namespace App\Api\V2\UserAwards;

use App\Community\Enums\AwardType;
use App\Models\PlayerBadge;
use App\Platform\Enums\UnlockMode;

class UserAwardPresenter
{
    public function __construct(
        private readonly PlayerBadge $award,
    ) {
    }

    public static function for(PlayerBadge $award): self
    {
        return new self($award);
    }

    public function kind(): string
    {
        return UserAwardKind::fromAward($this->award)->value;
    }

    public function title(): ?string
    {
        return match ($this->award->award_type) {
            AwardType::Mastery, AwardType::GameBeaten => $this->award->gameIfApplicable?->title,
            AwardType::Event => $this->award->eventIfApplicable?->title,
            AwardType::Playtest => $this->award->siteAwardIfApplicable?->label,
            AwardType::AchievementUnlocksYield => 'Achievements Earned by Others',
            AwardType::AchievementPointsYield => 'Achievement Points Earned by Others',
            AwardType::PatreonSupporter => 'Patreon Supporter',
            AwardType::CertifiedLegend => 'Certified Legend',
            AwardType::MediaContribution => 'Media Contribution',
        };
    }

    public function badgeUrl(): ?string
    {
        return match ($this->award->award_type) {
            AwardType::Mastery, AwardType::GameBeaten => $this->award->gameIfApplicable?->badge_url,

            AwardType::Event => $this->award->eventIfApplicable?->awards
                ?->firstWhere('tier_index', $this->award->display_award_tier ?? $this->award->award_tier)
                ?->badge_url ?? $this->award->eventIfApplicable?->badge_url,

            AwardType::Playtest => $this->award->siteAwardIfApplicable?->badge_url,
            AwardType::AchievementUnlocksYield => asset("/assets/images/badge/contribYield-{$this->award->award_key}.png"),
            AwardType::AchievementPointsYield => asset("/assets/images/badge/contribPoints-{$this->award->award_key}.png"),
            AwardType::PatreonSupporter => asset('/assets/images/badge/patreon.png'),
            AwardType::CertifiedLegend => asset('/assets/images/badge/legend.png'),
            AwardType::MediaContribution => mediaContributionBadgeUrl($this->award->displayed_tier),
        };
    }

    public function userId(): ?string
    {
        return $this->award->user?->ulid;
    }

    public function userDisplayName(): ?string
    {
        return $this->award->user?->display_name;
    }

    public function context(): array
    {
        return match ($this->award->award_type) {
            AwardType::Mastery, AwardType::GameBeaten => [
                'gameId' => $this->award->award_key,
                'mode' => $this->award->award_tier === UnlockMode::Hardcore ? 'hardcore' : 'casual',
            ],
            AwardType::Event => [
                'eventId' => $this->award->award_key,
                'tierIndex' => $this->award->award_tier,
                'displayTierIndex' => $this->award->display_award_tier ?? $this->award->award_tier,
                'grantsSiteAward' => $this->award->isSiteEventAward(),
            ],
            AwardType::Playtest => [
                'siteAwardId' => $this->award->award_key,
            ],
            AwardType::AchievementUnlocksYield, AwardType::AchievementPointsYield => [
                'tier' => $this->award->award_key,
                'threshold' => PlayerBadge::getBadgeThreshold($this->award->award_type, $this->award->award_key),
            ],
            AwardType::MediaContribution => $this->mediaContributionContext(),
            AwardType::PatreonSupporter, AwardType::CertifiedLegend => [
                'siteAwardType' => $this->award->award_type->value,
            ],
        };
    }

    /**
     * @return array{tier: int, displayTierIndex: int, earnedTier: int, threshold: int}
     */
    private function mediaContributionContext(): array
    {
        $displayTier = $this->award->displayed_tier;

        return [
            'tier' => $displayTier,
            'displayTierIndex' => $displayTier,
            'earnedTier' => $this->award->award_tier,
            'threshold' => PlayerBadge::getBadgeThreshold($this->award->award_type, $displayTier),
        ];
    }

    public function hasGameRelationship(): bool
    {
        return
            $this->award->isGameRelated()
            && $this->award->relationLoaded('gameIfApplicable')
            && $this->award->gameIfApplicable !== null;
    }

    public function hasEventRelationship(): bool
    {
        return
            $this->award->award_type === AwardType::Event
            && $this->award->relationLoaded('eventIfApplicable')
            && $this->award->eventIfApplicable !== null;
    }
}
