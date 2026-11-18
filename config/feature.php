<?php

return [
    /**
     * Disabling OAuth removes Passport's authorization, token, refresh, and
     * device routes. Existing access tokens remain valid until they expire.
     */
    'oauth' => env('FEATURE_OAUTH', false),
];
