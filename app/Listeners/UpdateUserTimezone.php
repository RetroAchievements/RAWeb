<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Support\Concerns\HandlesResources;
use Illuminate\Auth\Events\Login;
use Torann\GeoIP\Location;

class UpdateUserTimezone
{
    use HandlesResources;

    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        /** @var Location $geoIpInfo */
        $geoIpInfo = geoip(request()->server->get('REMOTE_ADDR'));

        /*
         * use getRawOriginal() to skip the getTimezoneAttribute() accessor which will set it to UTC by default
         */
        if (!$user->getRawOriginal('timezone') && $user->getRawOriginal('timezone') !== $geoIpInfo['timezone']) {
            $user->setAttribute('timezone', $geoIpInfo['timezone']);
        }
        // if (!$user->country && $user->country !== $geoIpInfo['iso_code']) {
        //     $user->setAttribute('country', $geoIpInfo['iso_code']);
        // }

        if ($user->isDirty()) {
            $user->save();
            // request()->session()->flash('success', $this->resourceActionSuccessMessage('user.timezone', 'update-to', $geoIpInfo['timezone']));
        }
    }
}
