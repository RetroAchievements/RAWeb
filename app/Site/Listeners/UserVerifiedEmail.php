<?php

declare(strict_types=1);

namespace App\Site\Listeners;

use Illuminate\Auth\Events\Verified;

class UserVerifiedEmail
{
    public function handle(Verified $event): void
    {
        // $event->user->save();
    }
}
