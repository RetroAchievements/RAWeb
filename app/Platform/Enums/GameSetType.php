<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum GameSetType: string
{
    case HUB = "hub";
    case SIMILAR_GAMES = "similar-games";
}
