<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('AwardType')]
abstract class AwardType
{
    // TODO refactor to AchievementSetCompleted
    public const Mastery = 1;

    public const AchievementUnlocksYield = 2;

    public const AchievementPointsYield = 3;

    // public const Referrals = 4;

    // public const FacebookConnect = 5;

    public const PatreonSupporter = 6;

    public const CertifiedLegend = 7;

    // TODO refactor to AchievementSetBeaten
    public const GameBeaten = 8;

    public static function cases(): array
    {
        return [
            self::Mastery,
            self::AchievementUnlocksYield,
            self::AchievementPointsYield,
            self::PatreonSupporter,
            self::CertifiedLegend,
            self::GameBeaten,
        ];
    }

    public static function isActive(int $value): bool
    {
        return in_array($value, self::cases());
    }

    public static function toString(int $awardType): string
    {
        return match ($awardType) {
            AwardType::Mastery => 'Mastery/Completion',
            AwardType::AchievementUnlocksYield => 'Achievement Unlocks Yield',
            AwardType::AchievementPointsYield => 'Achievement Points Yield',
            AwardType::PatreonSupporter => 'Patreon Supporter',
            AwardType::CertifiedLegend => 'Certified Legend',
            AwardType::GameBeaten => 'Game Beaten',
            default => 'Invalid or deprecated award type',
        };
    }

    public static function game(): array
    {
        return [
            self::Mastery,
            self::GameBeaten,
        ];
    }

    public static function isGame(int $type): bool
    {
        return in_array($type, static::game());
    }
}
