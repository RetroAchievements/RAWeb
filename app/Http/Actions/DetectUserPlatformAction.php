<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Enums\UserPlatform;
use Jenssegers\Agent\Agent;

class DetectUserPlatformAction
{
    public function execute(): ?UserPlatform
    {
        $agent = new Agent();

        if ($agent->is('Windows') || $agent->is('Windows NT')) {
            return UserPlatform::Windows;
        } elseif ($agent->is('OS X') && !$agent->isMobile()) {
            return UserPlatform::MacOS;
        } elseif ($agent->is('AndroidOS')) {
            return UserPlatform::Android;
        } elseif ($agent->is('Debian') || $agent->is('Ubuntu') || $agent->is('OpenBSD') || $agent->is('Linux')) {
            return UserPlatform::Linux;
        } elseif ($agent->is('iOS')) {
            return UserPlatform::IOS;
        }

        return null;
    }
}
