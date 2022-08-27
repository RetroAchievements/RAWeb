<?php

namespace RA;

abstract class FilenameIterator
{
    public const BadgeIterator = 'BadgeIter';
    public const ImageIterator = 'ImageIter';

    private const BadgeIteratorPadding = 5;
    private const ImageIteratorPadding = 6;

    public static function cases(): array
    {
        return [
            self::BadgeIterator,
            self::ImageIterator,
        ];
    }

    public static function isValidIterator(string $iterator): bool
    {
        return in_array($iterator, self::cases());
    }

    public static function get(string $iterator): int
    {
        return self::isValidIterator($iterator)
            ? (int) file_get_contents(storage_path('app/' . $iterator . '.txt'))
            : 0;
    }

    public static function getBadgeIterator(): string
    {
        return str_pad((string) self::get(self::BadgeIterator), self::BadgeIteratorPadding, "0", STR_PAD_LEFT);
    }

    public static function getImageIterator(): string
    {
        return str_pad((string) self::get(self::ImageIterator), self::ImageIteratorPadding, "0", STR_PAD_LEFT);
    }

    public static function incrementBadgeIterator(): void
    {
        file_put_contents(
            storage_path('app/' . self::BadgeIterator . '.txt'),
            str_pad((string) (self::get(self::BadgeIterator) + 1), self::BadgeIteratorPadding, "0", STR_PAD_LEFT)
        );
    }

    public static function incrementImageIterator(): void
    {
        file_put_contents(
            storage_path('app/' . self::ImageIterator . '.txt'),
            str_pad((string) (self::get(self::ImageIterator) + 1), self::ImageIteratorPadding, "0", STR_PAD_LEFT)
        );
    }
}
