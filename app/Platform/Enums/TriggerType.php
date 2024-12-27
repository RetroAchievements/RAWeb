<?php

declare(strict_types=1);

namespace App\Platform\Enums;

/**
 * In the event that emulators can ever process something besides RATrigger
 * code (ie: RAScript, Lua, etc), it would be an additional case in this enum.
 */
enum TriggerType: string
{
    case RATrigger = 'ratrigger';
}
