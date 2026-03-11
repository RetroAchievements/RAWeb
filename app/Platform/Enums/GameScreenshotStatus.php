<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum GameScreenshotStatus: string
{
    /** This screenshot is viewable by all players. */
    case Approved = 'approved';

    /** This screenshot is in a review queue, and is only visible to the uploader and reviewers. */
    case Pending = 'pending';

    /** This screenshot was either approved or pending, but ultimately rejected for public viewing. */
    case Rejected = 'rejected';
}
