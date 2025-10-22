<?php

declare(strict_types=1);

namespace App\Support\Media;

abstract class FilenameIterator
{
    public const ImageIterator = 'ImageIter';

    private const ImageIteratorPadding = 6;

    public static function cases(): array
    {
        return [
            self::ImageIterator,
        ];
    }

    public static function isValidIterator(string $iterator): bool
    {
        return in_array($iterator, self::cases());
    }

    public static function get(string $iterator): int
    {
        if (!self::isValidIterator($iterator)) {
            return 0;
        }

        if (!file_exists(storage_path('app/' . $iterator . '.txt'))) {
            file_put_contents(storage_path('app/' . $iterator . '.txt'), 2);
        }

        return (int) file_get_contents(storage_path('app/' . $iterator . '.txt'));
    }

    public static function getImageIterator(): string
    {
        return str_pad((string) self::get(self::ImageIterator), self::ImageIteratorPadding, "0", STR_PAD_LEFT);
    }

    public static function incrementImageIterator(): void
    {
        file_put_contents(
            storage_path('app/' . self::ImageIterator . '.txt'),
            str_pad((string) (self::get(self::ImageIterator) + 1), self::ImageIteratorPadding, "0", STR_PAD_LEFT)
        );
    }
}
