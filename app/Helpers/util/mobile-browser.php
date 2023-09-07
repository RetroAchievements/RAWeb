<?php

use Jenssegers\Agent\Agent;

function IsMobileBrowser(): bool
{
    $agent = new Agent();

    return $agent->isMobile();
}
