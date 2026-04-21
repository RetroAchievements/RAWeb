<?php

declare(strict_types=1);

return [
    Jenssegers\Agent\AgentServiceProvider::class,
    Laravel\Passport\PassportServiceProvider::class,

    App\Connect\RouteServiceProvider::class,
    App\Api\RouteServiceProvider::class,

    App\Platform\AppServiceProvider::class,
    App\Platform\AuthServiceProvider::class,
    App\Platform\EventServiceProvider::class,
    App\Platform\RouteServiceProvider::class,

    App\Community\AppServiceProvider::class,
    App\Community\AuthServiceProvider::class,
    App\Community\EventServiceProvider::class,
    App\Community\RouteServiceProvider::class,

    App\Support\Filesystem\FilesystemServiceProvider::class,
    App\Support\Settings\SettingsServiceProvider::class,

    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    App\Providers\FolioServiceProvider::class,

    App\Filament\FilamentServiceProvider::class,
];
