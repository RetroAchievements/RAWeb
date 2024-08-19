<?php

/**
 * @see https://github.com/tighten/ziggy?tab=readme-ov-file#filtering-routes
 */

return [
    'output' => [
        'path' => 'resources/js',
    ],
    'except' => [
        'demo.*',
        'debugbar.*',
        'filament.*',
        'horizon.*',
        'livewire.*',
        'log-viewer.*',
        'ignition.*',
        'laravel-folio',
    ],
];
