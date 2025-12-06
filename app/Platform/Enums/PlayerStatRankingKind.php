<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum PlayerStatRankingKind: string
{
    case RetailBeaten = 'retail_beaten';
    case HomebrewBeaten = 'homebrew_beaten';
    case HacksBeaten = 'hacks_beaten';
    case AllBeaten = 'all_beaten';

    // TODO points rankings, RetroPoints rankings

    /**
     * @return array<self>
     */
    public static function beatenCases(): array
    {
        return [
            self::RetailBeaten,
            self::HomebrewBeaten,
            self::HacksBeaten,
            self::AllBeaten,
        ];
    }
}
