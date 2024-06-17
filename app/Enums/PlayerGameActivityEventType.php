<?php

declare(strict_types=1);

namespace App\Enums;

abstract class PlayerGameActivityEventType
{
    public const Unlock = 'unlock';

    public const RichPresence = 'rich-presence';

    public const Custom = 'custom';
}
