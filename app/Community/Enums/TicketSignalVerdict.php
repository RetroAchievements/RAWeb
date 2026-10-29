<?php

declare(strict_types=1);

namespace App\Community\Enums;

enum TicketSignalVerdict: string
{
    case ProbablyNotDefect = 'probably-not-defect';
    case ProbablyDefect = 'probably-defect';
}
