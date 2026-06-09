<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum ScreenshotReviewDecision: string
{
    case Primary = 'primary';
    case PrimaryKeepGallery = 'primary_keep_gallery';
    case Gallery = 'gallery';
    case Reject = 'reject';
}
