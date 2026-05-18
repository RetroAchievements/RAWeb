<?php

/**
 * @see https://github.com/tighten/ziggy?tab=readme-ov-file#filtering-routes
 */

return [
    'output' => [
        'path' => 'resources/js',
    ],
    'except' => [
        'api.internal.*',
        'api.v1.*',
        'api.v2.*',
        'demo.*',
        'debugbar.*',
        'default-livewire.*',
        'filament.*',
        'horizon.*',
        'internal.*',
        'livewire.*',
        'log-viewer.*',
        'ignition.*',
        'laravel-folio',
        'pulse',
        'v2.*',
    ],
];
