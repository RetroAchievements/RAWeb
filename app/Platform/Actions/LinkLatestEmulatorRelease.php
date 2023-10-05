<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Models\Emulator;

class LinkLatestEmulatorRelease
{
    public function execute(Emulator $emulator): void
    {
        /*
         * Link the latest version to the fallback location for backwards compatibility
         * Note: it does not have to be the minimum version
         * Note: it should make a difference between normal and x64 files
         */
    }
}
