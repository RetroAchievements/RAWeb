<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum GameSetRolePermission: string
{
    case View = 'view';
    case Update = 'update';
}
