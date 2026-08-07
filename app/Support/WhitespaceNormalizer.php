<?php

declare(strict_types=1);

namespace App\Support;

final class WhitespaceNormalizer
{
    private const INVISIBLE = '\x{0009}\x{000A}\x{000B}\x{000C}\x{000D}\x{0085}\x{00A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}';
    private const ZERO_WIDTH = '\x{00AD}\x{200B}\x{FEFF}';

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/[' . self::ZERO_WIDTH . ']/u', '', $value);
        $value = preg_replace('/[' . self::INVISIBLE . '\x{0020}]+/u', ' ', (string) $value);

        return trim((string) $value);
    }

    public static function hasInvisible(string $value): bool
    {
        return (bool) preg_match('/[' . self::ZERO_WIDTH . self::INVISIBLE . ']/u', $value);
    }
}
