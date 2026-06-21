<?php

declare(strict_types=1);

namespace App\Api\V2\UserAwards;

use App\Community\Enums\AwardType;
use App\Models\PlayerBadge;
use App\Platform\Enums\UnlockMode;
use Illuminate\Database\Eloquent\Builder;

enum UserAwardKind: string
{
    case AchievementPointsYield = 'achievement-points-yield';
    case AchievementUnlocksYield = 'achievement-unlocks-yield';
    case BeatenCasual = 'beaten-casual';
    case BeatenHardcore = 'beaten-hardcore';
    case CertifiedLegend = 'certified-legend';
    case Completed = 'completed';
    case Event = 'event';
    case Mastered = 'mastered';
    case MediaContribution = 'media-contribution';
    case PatreonSupporter = 'patreon-supporter';
    case Playtest = 'playtest';

    public static function fromAward(PlayerBadge $award): self
    {
        return match ($award->award_type) {
            AwardType::Mastery => $award->award_tier === UnlockMode::Hardcore ? self::Mastered : self::Completed,
            AwardType::GameBeaten => $award->award_tier === UnlockMode::Hardcore ? self::BeatenHardcore : self::BeatenCasual,
            AwardType::Event => self::Event,
            AwardType::Playtest => self::Playtest,
            AwardType::AchievementUnlocksYield => self::AchievementUnlocksYield,
            AwardType::AchievementPointsYield => self::AchievementPointsYield,
            AwardType::PatreonSupporter => self::PatreonSupporter,
            AwardType::CertifiedLegend => self::CertifiedLegend,
            AwardType::MediaContribution => self::MediaContribution,
        };
    }

    /**
     * @param Builder<PlayerBadge> $query
     * @return Builder<PlayerBadge>
     */
    public function apply(Builder $query): Builder
    {
        return match ($this) {
            self::Mastered => $this->applyTiered($query, AwardType::Mastery, UnlockMode::Hardcore),
            self::Completed => $this->applyTiered($query, AwardType::Mastery, UnlockMode::Casual),
            self::BeatenHardcore => $this->applyTiered($query, AwardType::GameBeaten, UnlockMode::Hardcore),
            self::BeatenCasual => $this->applyTiered($query, AwardType::GameBeaten, UnlockMode::Casual),
            self::Event => $query->where('award_type', AwardType::Event),
            self::Playtest => $query->where('award_type', AwardType::Playtest),
            self::AchievementUnlocksYield => $query->where('award_type', AwardType::AchievementUnlocksYield),
            self::AchievementPointsYield => $query->where('award_type', AwardType::AchievementPointsYield),
            self::PatreonSupporter => $query->where('award_type', AwardType::PatreonSupporter),
            self::CertifiedLegend => $query->where('award_type', AwardType::CertifiedLegend),
            self::MediaContribution => $query->where('award_type', AwardType::MediaContribution),
        };
    }

    /**
     * @param Builder<PlayerBadge> $query
     * @return Builder<PlayerBadge>
     */
    private function applyTiered(Builder $query, AwardType $awardType, int $unlockMode): Builder
    {
        return $query
            ->where('award_type', $awardType)
            ->where('award_tier', $unlockMode);
    }
}
