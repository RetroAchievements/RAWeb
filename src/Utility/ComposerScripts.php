<?php

namespace RA\Utility;

use Composer\Script\Event;

class ComposerScripts
{
    /**
     * Run scripts that follow only if dev packages are installed.
     * https://github.com/BrainMaestro/composer-git-hooks/issues/100#issuecomment-563442163
     */
    public static function devModeOnly(Event $event)
    {
        if (!$event->isDevMode()) {
            $event->stopPropagation();
            echo "Skipping {$event->getName()} as this is a non-dev installation.\n";
        }
    }
}
