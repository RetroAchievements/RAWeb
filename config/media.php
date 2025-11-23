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
    /**
     * profile banners (future feature)
     * given a user beats a game that has a custom banner, users can use that banner on their profile
     * when user selects a game banner, a COPY is stored in the profile_banner library as a snapshot
     * this ensures their profile banner doesn't change if the game's banner is updated
     * uses the same sizes as game.banner for consistency
     * does NOT use singleFile() - users can save multiple banners
     *
     * implementation approach:
     * 1. user beats a game -> unlocks ability to use that game's banner
     * 2. user selects the banner -> copy media from game.banner to user.profile_banner
     * 3. store source game_id in custom properties for attribution
     * 4. user can collect multiple banners (1 per beaten game)
     * 5. user selects active banner via some preference setting
     *
     * storage considerations:
     * - game banners remain on singleFile() for S3 disk efficiency
     * - user profile banners are stable independent snapshots
     */
        // 'profile_banner' => [
        //     'mobile-sm' => [...], // same sizes as game.banner
        //     'mobile-md' => [...],
        //     'desktop-md' => [...],
        //     'desktop-lg' => [...],
        //     'desktop-xl' => [...],
        // ],
    ],
];
