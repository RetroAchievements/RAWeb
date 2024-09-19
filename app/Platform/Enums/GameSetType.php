<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum GameSetType: string
{
    case Hub = "hub";
    case SimilarGames = "similar-games";
}
