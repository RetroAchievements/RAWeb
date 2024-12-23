<?php

declare(strict_types=1);

namespace App\Actions;

use Jenssegers\Agent\Agent;

class GetUserDeviceKindAction
{
    /**
     * Determines the user's device type based on user agent detection.
     *
     * Returns 'mobile' for non-tablet mobile devices.
     * Returns 'desktop' for everything else.
     *
     * @return string either 'mobile' or 'desktop'
     */
    public function execute(): string
    {
        $agent = new Agent();

        $hasMobileDevice = $agent->isMobile();
        $hasTabletDevice = $agent->isTablet();

        if ($hasMobileDevice && !$hasTabletDevice) {
            return 'mobile';
        }

        return 'desktop';
    }
}
