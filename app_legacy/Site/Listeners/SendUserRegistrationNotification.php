<?php

declare(strict_types=1);

namespace LegacyApp\Site\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Notification;
use LegacyApp\Site\Models\User;
use LegacyApp\Site\Notifications\UserRegistrationNotification;

class SendUserRegistrationNotification
{
    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->user;
        Notification::route('webhook', config('services.discord.webhook.users'))
            ->notify(new UserRegistrationNotification($user));
    }
}
