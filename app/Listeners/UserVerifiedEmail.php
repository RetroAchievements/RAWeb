<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\Verified;

class UserVerifiedEmail
{
    public function handle(Verified $event): void
    {
        // $event->user->save();
    }
}
