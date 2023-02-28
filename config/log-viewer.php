<?php

return [
    /*
     * Log Viewer route path.
     */
    'route_path' => 'log-viewer',

    /*
     * When set, displays a link to easily get back to this URL.
     * Set to `null` to hide this link.
     */
    'back_to_system_url' => config('app.url'),

    /*
     * Optional label to display for the above URL. Defaults to "Back to {{ app.name }}"
     */
    'back_to_system_label' => null,

    /*
     * Log Viewer route middleware.
     * The middleware should enable session and cookies support in order for the Log Viewer to work.
     * The 'web' middleware will be applied automatically if empty.
     */
    'middleware' => ['web', 'auth', 'can:viewLogs'],

    /*
     * Include file patterns
     */
    'include_files' => ['laravel*.log'],

    /*
     * Exclude file patterns. This will take precedence
     */
    'exclude_files' => [],

    /*
     * Shorter stack trace filters. Any lines containing any of the below strings will be excluded from the full log.
     * Only active when the setting is on, which can be toggled in the user interface.
     */
    'shorter_stack_trace_excludes' => [
        '/vendor/symfony/',
        '/vendor/laravel/framework/',
        '/vendor/barryvdh/laravel-debugbar/',
    ],
];
