<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Enums\UserOS;
use Jenssegers\Agent\Agent;

class DetectUserOSAction
{
    public function execute(): ?UserOS
    {
        $agent = new Agent();

        if ($agent->is('Windows') || $agent->is('Windows NT')) {
            return UserOS::Windows;
        } elseif ($agent->is('OS X') && !$agent->isMobile()) {
            return UserOS::MacOS;
        } elseif ($agent->is('AndroidOS')) {
            return UserOS::Android;
        } elseif ($agent->is('Debian') || $agent->is('Ubuntu') || $agent->is('OpenBSD') || $agent->is('Linux')) {
            return UserOS::Linux;
        } elseif ($agent->is('iOS')) {
            return UserOS::IOS;
        }

        return null;
    }
}
