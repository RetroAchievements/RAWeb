<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum GameScreenshotRejectionReason: string
{
    case WrongGame = 'wrong_game';
    case PoorQuality = 'poor_quality';
    case Duplicate = 'duplicate'; // too similar to another image in the game's gallery
    case IncorrectType = 'incorrect_type';
    case WrongResolution = 'wrong_resolution';
    case MissingMatchingCompanion = 'missing_matching_companion';
    case ManuallyResized = 'manually_resized';
    case Spoiler = 'spoiler';
    case InappropriateContent = 'inappropriate_content';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::WrongGame => 'Wrong Game',
            self::PoorQuality => 'Poor Quality',
            self::Duplicate => 'Duplicate',
            self::IncorrectType => 'Incorrect Type',
            self::WrongResolution => 'Wrong Resolution',
            self::MissingMatchingCompanion => 'Missing Matching Companion',
            self::ManuallyResized => 'Manually Resized or Upscaled',
            self::Spoiler => 'Spoiler Concern',
            self::InappropriateContent => 'Inappropriate Content',
            self::Other => 'Other',
        };
    }
}
