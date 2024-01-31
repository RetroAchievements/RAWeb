<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Broadcast::routes();

        Broadcast::channel('App.Models.User.{id}', fn ($user, $id) => (int) $user->id === (int) $id);
    }
}
