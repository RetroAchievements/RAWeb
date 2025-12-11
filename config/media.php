<?php

return [
    /**
     * used for user avatars, badges, icons ...
     */
    'icon' => [
        'sm' => [
            'width' => 32,
            'height' => 32,
        ],
        'md' => [
            'width' => 64,
            'height' => 64,
        ],
        'lg' => [
            'width' => 96,
            'height' => 96,
        ],
        'xl' => [
            'width' => 128,
            'height' => 128,
        ],
        '2xl' => [
            'width' => 192,
            'height' => 192,
        ],
    ],

    /**
     * used for news, ...
     */
    'header' => [
        '2xl' => [

        ],
    ],

    /**
     * used for game-related media
     * TODO: aspect ratio should be defined by system
     */
    'game' => [
        /**
         * banner images
         * game editors upload 32:9 source images
         * mobile uses 16:9 crops, desktop uses full 32:9
         * uses singleFile() - only keeps the latest version to minimize storage costs
         */
        'banner' => [
            'mobile-sm' => [
                'width' => 640,
                'height' => 360,
            ],
            'mobile-md' => [
                'width' => 1024,
                'height' => 576,
            ],
            'desktop-md' => [
                'width' => 1024,
                'height' => 288,
            ],
            'desktop-lg' => [
                'width' => 1280,
                'height' => 360,
            ],
            'desktop-xl' => [
                'width' => 1920,
                'height' => 540,
            ],
        ],

        // Future game media types can go here:
        // 'screenshot' => [...],
        // 'boxart' => [...],
    ],

    /**
     * used for user-related media
     */
    'user' => [

    ],
];
