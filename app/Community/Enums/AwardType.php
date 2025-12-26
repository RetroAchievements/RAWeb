<?php

declare(strict_types=1);

namespace App\Community\Enums;

use InvalidArgumentException;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum AwardType: string
{
    // TODO refactor to AchievementSetCompleted
    case Mastery = 'mastery';

    case AchievementUnlocksYield = 'achievement_unlocks_yield';

    case AchievementPointsYield = 'achievement_points_yield';

    case PatreonSupporter = 'patreon_supporter';

    case CertifiedLegend = 'certified_legend';

    case GameBeaten = 'game_beaten';

    case Event = 'event';

    /**
     * Returns all standard award type cases, excluding Event.
     * Event is excluded because it's handled specially and shouldn't
     * appear in typical award type iterations.
     */
    public static function standardCases(): array
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

    public static function isActive(self|int $value): bool
    {
        if (is_int($value)) {
            try {
                $value = self::fromLegacyInteger($value);
            } catch (InvalidArgumentException) {
                return false;
            }
        }

        return in_array($value, self::standardCases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Mastery => 'Mastery/Completion',
            self::AchievementUnlocksYield => 'Achievement Unlocks Yield',
            self::AchievementPointsYield => 'Achievement Points Yield',
            self::PatreonSupporter => 'Patreon Supporter',
            self::CertifiedLegend => 'Certified Legend',
            self::GameBeaten => 'Game Beaten',
            self::Event => 'Event',
        };
    }

    /**
     * Returns the legacy integer value for V1 API backwards compatibility.
     * These values were used when AwardType was an integer-backed enum
     * and must remain stable for existing API consumers.
     */
    public function toLegacyInteger(): int
    {
        return match ($this) {
            self::Mastery => 1,
            self::AchievementUnlocksYield => 2,
            self::AchievementPointsYield => 3,
            self::PatreonSupporter => 6,
            self::CertifiedLegend => 7,
            self::GameBeaten => 8,
            self::Event => 9,
        };
    }

    /**
     * Creates an AwardType from a legacy integer value.
     * Used for backwards compatibility with legacy code that still uses integer values.
     */
    public static function fromLegacyInteger(int $value): self
    {
        return match ($value) {
            1 => self::Mastery,
            2 => self::AchievementUnlocksYield,
            3 => self::AchievementPointsYield,
            6 => self::PatreonSupporter,
            7 => self::CertifiedLegend,
            8 => self::GameBeaten,
            9 => self::Event,
            default => throw new InvalidArgumentException("Invalid legacy AwardType value: {$value}"),
        };
    }

    /**
     * Returns game-related award types (Mastery, GameBeaten).
     */
    public static function game(): array
    {
        return [
            self::Mastery,
            self::GameBeaten,
        ];
    }

    /**
     * Returns game-related award type values as strings for SQL queries.
     */
    public static function gameValues(): array
    {
        return array_map(fn (self $type) => $type->value, self::game());
    }

    /**
     * Checks if the given award type is game-related (Mastery or GameBeaten).
     * Accepts either an AwardType enum or a legacy integer value.
     */
    public static function isGame(self|int $type): bool
    {
        if (is_int($type)) {
            try {
                $type = self::fromLegacyInteger($type);
            } catch (InvalidArgumentException) {
                return false;
            }
        }

        return in_array($type, self::game());
    }
}
