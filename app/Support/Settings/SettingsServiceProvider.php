<?php

declare(strict_types=1);

namespace App\Support\Settings;

use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $settings = new Settings();
        $this->app->instance(Settings::class, $settings);
        view()->share('settings', $settings);

        if ($this->app->runningInConsole()) {
            $this->commands([
            ]);
        }
    }
}
