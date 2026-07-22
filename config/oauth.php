<?php

declare(strict_types=1);

return [
    'max_applications_per_user' => (int) env('OAUTH_MAX_APPLICATIONS_PER_USER', 5),
];
