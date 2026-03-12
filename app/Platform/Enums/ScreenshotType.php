<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum ScreenshotType: string
{
    case Title = 'title';
    case Ingame = 'ingame';
    case Completion = 'completion';
}
