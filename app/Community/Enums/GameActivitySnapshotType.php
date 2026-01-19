<?php

declare(strict_types=1);

namespace App\Community\Enums;

enum GameActivitySnapshotType: string
{
    case Trending = 'trending';
    case Popular = 'popular';
}
