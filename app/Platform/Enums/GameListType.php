<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum GameListType
{
    case UserPlay;
    case UserDevelop;
    case AllGames;
    case System;
    case Hub;
    case DeveloperSets;
}
