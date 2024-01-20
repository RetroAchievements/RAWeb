<?php

declare(strict_types=1);

namespace App\Platform\Enums;

abstract class ValueFormat
{
    // value padded to six digits with leading zeros
    public const Score = 'SCORE';

    // mm:ss.cc calculated by taking value / 60
    public const TimeFrames = 'TIME';

    // mm:ss.cc calculated by taking value / 100
    public const TimeCentiseconds = 'MILLISECS';

    // hhhmm:ss
    public const TimeSeconds = 'TIMESECS';

    // hhhmm
    public const TimeMinutes = 'MINUTES';

    // number followed by three zeroes
    public const ValueThousands = 'THOUSANDS';

    // number followed by two zeroes
    public const ValueHundreds = 'HUNDREDS';

    // number followed by one zero
    public const ValueTens = 'TENS';

    // number
    public const Value = 'VALUE';

    // unsigned number
    public const ValueUnsigned = 'UNSIGNED';

    // n.n calculated by taking number / 10
    public const Fixed1 = 'FIXED1';

    // n.nn calculated by taking number / 100
    public const Fixed2 = 'FIXED2';

    // n.nnn calculated by taking number / 1000
    public const Fixed3 = 'FIXED3';

    public static function cases(): array
    {
        return [
            self::Score,
            self::TimeFrames,
            self::TimeCentiseconds,
            self::TimeSeconds,
            self::TimeMinutes,
            self::ValueThousands,
            self::ValueHundreds,
            self::ValueTens,
            self::Value,
            self::ValueUnsigned,
            self::Fixed1,
            self::Fixed2,
            self::Fixed3,
        ];
    }

    public static function isValid(string $format): bool
    {
        return in_array($format, self::cases());
    }

    public static function toString(string $format): string
    {
        return match ($format) {
            self::Score => 'Score',
            self::TimeFrames => 'Time (Frames)',
            self::TimeCentiseconds => 'Time (Centiseconds)',
            self::TimeSeconds => 'Time (Seconds)',
            self::TimeMinutes => 'Time (Minutes)',
            self::ValueThousands => 'Value (Thousands)',
            self::ValueHundreds => 'Value (Hundreds)',
            self::ValueTens => 'Value (Tens)',
            self::Value => 'Value',
            self::ValueUnsigned => 'Value (unsigned)',
            self::Fixed1 => 'Fixed1',
            self::Fixed2 => 'Fixed2',
            self::Fixed3 => 'Fixed3',
            default => 'Unknown',
        };
    }

    public static function format(int $value, string $format): string
    {
        if ($format === self::ValueUnsigned) {
            return localized_number(ValueFormat::toUnsignedInt32($value));
        }

        $value = self::toSignedInt32($value);

        // NOTE: a/b results in a float, a%b results in an integer
        return match ($format) {
            self::Score => sprintf("%06d", $value),
            self::TimeFrames => sprintf("%s.%02d", ValueFormat::formatSeconds((int) ($value / 60)), ($value % 60) * 100 / 60),
            self::TimeCentiseconds => sprintf("%s.%02d", ValueFormat::formatSeconds((int) ($value / 100)), $value % 100),
            self::TimeSeconds => ValueFormat::formatSeconds($value),
            self::TimeMinutes => sprintf("%01dh%02d", (int) $value / 60, $value % 60),
            self::ValueThousands => localized_number($value * 1000),
            self::ValueHundreds => localized_number($value * 100),
            self::ValueTens => localized_number($value * 10),
            self::Fixed1 => localized_number((float) $value / 10, fractionDigits: 1),
            self::Fixed2 => localized_number((float) $value / 100, fractionDigits: 2),
            self::Fixed3 => localized_number((float) $value / 1000, fractionDigits: 3),
            default => localized_number($value),
        };
    }

    private static function toUnsignedInt32(int $value): int
    {
        if ($value < 0) {
            $value = 0xFFFFFFFF + $value + 1;
        }

        return min($value, 0xFFFFFFFF);
    }

    private static function toSignedInt32(int $value): int
    {
        if ($value >= 0x80000000) {
            $value = -(0xFFFFFFFF - $value + 1);
        }

        return max($value, -0x80000000);
    }

    private static function formatSeconds(int $seconds): string
    {
        $hours = (int) ($seconds / 3600);
        $mins = (int) ($seconds / 60) % 60;
        $secs = $seconds % 60;

        if ($hours === 0) {
            return sprintf("%01d:%02d", $mins, $secs);
        }

        return sprintf("%01dh%02d:%02d", $hours, $mins, $secs);
    }
}
