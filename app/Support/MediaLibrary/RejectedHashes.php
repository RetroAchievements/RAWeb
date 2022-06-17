<?php

declare(strict_types=1);

namespace App\Support\MediaLibrary;

class RejectedHashes
{
    public const AVATAR_HASHES = [
        /*
         * default avatar
         */
        '1f8e23e548498f98c6cfcb3822e19c417aea814f',
        /*
         * blurry variants of the default avatar
         */
        'db6ed38f40827480a7736aa49d149edb8df101c4',
        'ad13bfcfc28cd686edc4673827d6dd7edab2b209',
        '565ccb7872f1ca77e330c69ae88e2764e97ab69c',
        '4e21b3d209dd06db693b4237002d1b1c3d4c9a28',
        'cb31c13814c2e3f8bc3e7a932269d830631df1e9',
        '5fb22737dc091a56ed5859674e112154498da4aa',
        '06a2af154c9e0d1e4c74ad7d0ad3df6987376a5e',
        '433e0eb1315d956a1e2513bbc439f37296ce508d',
        /*
         * "RetroAchievements User"
         */
        'c435dfa9b08cfcea5db84afdb29453f4db9bb45a',
        /*
         * zero byte size
         */
        'da39a3ee5e6b4b0d3255bfef95601890afd80709',
        // /**
        //  * black avatar
        //  */
        // '98e4906b670b27d65699a6c4709660e4650655df',
        // '5ca655c6c604bb2b397a44ac6b5b7a8ed6b26044',
        // /**
        //  * white avatar
        //  */
        // '72c080b7b162d47d293cc1e3aea23fce5e70b0c7',
    ];

    public const IMAGE_HASHES_NEWS = [
        /*
         * black background
         */
        'f8c8c37487fe02ab42de18151ba2dcbc9cda3347',
    ];

    public const IMAGE_HASHES_GAMES = [
        /*
         * default icon
         */
        'e4f6517093e0f725b01a2cd3e4f722a61dc11b63',
        /*
         * default screenshot
         */
        '', // /Images/000002.png
    ];
}
