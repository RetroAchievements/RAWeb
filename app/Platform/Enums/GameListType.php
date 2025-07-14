<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum GameListType
{
    case AllGames;
    case DeveloperSets;
    case GameSpecificSuggestions;
    case Hub;
    case SetRequests;
    case System;
    case UserDevelop;
    case UserPlay;
    case UserSpecificSuggestions;
}
