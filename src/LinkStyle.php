<?php

namespace RA;

abstract class LinkStyle
{
    public const Text = 0;
    public const TinyImage = 1; // 16x16
    public const SmallImage = 2; // 24x24
    public const MediumImage = 3; // 32x32
    public const LargeImage = 4; // 48x48
    public const ExtraLargeImage = 5; // 64x64
    public const TinyImageWithText = 6; // 16x16
    public const SmallImageWithText = 7; // 24x24
    public const MediumImageWithText = 8; // 32x32
    public const LargeImageWithText = 9; // 48x48
    public const ExtraLargeImageWithText = 10; // 64x64

    public static function hasText(int $style): bool
    {
        return match ($style) {
            LinkStyle::Text => true,
            LinkStyle::TinyImageWithText => true,
            LinkStyle::SmallImageWithText => true,
            LinkStyle::MediumImageWithText => true,
            LinkStyle::LargeImageWithText => true,
            LinkStyle::ExtraLargeImageWithText => true,
            default => false,
        };
    }

    public static function getImageSize(int $style): int
    {
        return match ($style) {
            LinkStyle::TinyImage => 16,
            LinkStyle::TinyImageWithText => 16,
            LinkStyle::SmallImage => 24,
            LinkStyle::SmallImageWithText => 24,
            LinkStyle::MediumImage => 32,
            LinkStyle::MediumImageWithText => 32,
            LinkStyle::LargeImage => 48,
            LinkStyle::LargeImageWithText => 48,
            LinkStyle::ExtraLargeImage => 64,
            LinkStyle::ExtraLargeImageWithText => 64,
            default => 0,
        };
    }
}
