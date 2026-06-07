<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum ScreenshotReviewDecision: string
{
    case Primary = 'primary';
    case Gallery = 'gallery';
    case Reject = 'reject';
}
