<?php

declare(strict_types=1);

namespace LegacyApp\Community\Enums;

enum RequestStatus: int
{
    case ANY = 0;
    case CLAIMED = 1;
    case UNCLAIMED = 2;
}
