<?php

declare(strict_types=1);

namespace App\Filament\Enums;

enum ImageUploadType
{
    case News;
    case HubBadge;
    case GameBadge;
    case GameBoxArt;
    case GameTitle;
    case GameInGame;
    case AchievementBadge;
    case EventAward;
}
