<?php

namespace RA;

abstract class ImageType
{
    public const GameIcon = 'game_icon';
    public const GameTitle = 'game_title';
    public const GameBoxArt = 'game_box_art';
    public const GameInGame = 'game_in_game';

    public const cases = [
        self::GameIcon,
        self::GameTitle,
        self::GameBoxArt,
        self::GameInGame,
    ];

    public static function isValidGameImageType(string $type): bool
    {
        return in_array($type, self::cases);
    }
}
