<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum GlobalRankingWindow: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case AllTime = 'alltime';
}
