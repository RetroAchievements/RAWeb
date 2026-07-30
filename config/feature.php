<?php

return [
    'game_screenshot_uploads' => env('FEATURE_GAME_SCREENSHOT_UPLOADS', false),

    /**
     * Disabling OAuth removes Passport's authorization, token, refresh, and
     * device routes. Existing access tokens remain valid until they expire.
     */
    'oauth' => env('FEATURE_OAUTH', false),
];
