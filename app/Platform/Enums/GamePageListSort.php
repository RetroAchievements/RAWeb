<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum GamePageListSort: string
{
    case Normal = 'normal';
    case NormalReverse = '-normal';
    case DisplayOrder = 'displayOrder';
    case DisplayOrderReverse = '-displayOrder';
    case WonBy = 'wonBy';
    case WonByReverse = '-wonBy';
    case Points = 'points';
    case PointsReverse = '-points';
    case Title = 'title';
    case TitleReverse = '-title';
    case Type = 'type';
    case TypeReverse = '-type';
}
