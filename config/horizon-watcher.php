<?php

return [
    /*
     * Horizon will be restarted when any PHP file inside these directories
     * get created, updated or deleted. You can also specify other kinds
     * of files here.
     */
    'paths' => [
        app_path(),
        config_path(),
        database_path(),
        resource_path('views'),
        base_path('.env'),
        base_path('composer.lock'),
    ],

    /*
     * This command will be executed to start Horizon.
     */
    'command' => 'php artisan horizon',
];
