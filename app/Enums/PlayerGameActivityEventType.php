<?php

declare(strict_types=1);

namespace App\Enums;

enum PlayerGameActivityEventType: string
{
    case Unlock = 'unlock';

    case RichPresence = 'rich-presence';

    case Reset = 'reset';

    case Custom = 'custom';
}
