<?php

declare(strict_types=1);

namespace App\Platform\Enums;

abstract class ImageType
{
    public const GameIcon = 'game_icon';
    public const GameTitle = 'game_title';
    public const GameBoxArt = 'game_box_art';
    public const GameInGame = 'game_in_game';

    public static function cases(): array
    {
        return [
            self::GameIcon,
            self::GameTitle,
            self::GameBoxArt,
            self::GameInGame,
        ];
    }
}
