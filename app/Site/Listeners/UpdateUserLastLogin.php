<?php

declare(strict_types=1);

namespace App\Site\Listeners;

use App\Site\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;

class UpdateUserLastLogin
{
    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $user->last_login_at = Carbon::now();
        $user->save();
    }
}
