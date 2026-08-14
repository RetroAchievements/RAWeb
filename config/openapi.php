<?php

declare(strict_types=1);

use App\Enums\OAuthScope;

$scopes = [];
foreach (OAuthScope::cases() as $scope) {
    $scopes[$scope->value] = $scope->description();
}

return [
    'servers' => [
        'v2' => [
            'url' => 'https://api.retroachievements.org/api/v2',

            'info' => [
                'title' => 'RetroAchievements API',
                'description' => 'The RetroAchievements JSON:API.',
                'version' => '2.0.0',
                'license' => [
                    'name' => 'GPL-3.0-only',
                    'url' => 'https://github.com/RetroAchievements/RAWeb/blob/master/LICENSE',
                ],
            ],

            'securitySchemes' => [
                'ApiKey' => [
                    'middleware' => ['auth:api-token-header,oauth'],
                    'type' => 'apiKey',
                    'in' => 'header',
                    'name' => 'X-API-Key',
                    'description' => 'A web API key, sent as the X-API-Key header.',
                ],

                'OAuth2' => [
                    'middleware' => ['auth:api-token-header,oauth'],
                    'type' => 'oauth2',
                    'flows' => [
                        // These must be absolute URLs.
                        'authorizationCode' => [
                            'authorizationUrl' => 'https://retroachievements.org/oauth/authorize',
                            'tokenUrl' => 'https://retroachievements.org/oauth/token',
                            'refreshUrl' => 'https://retroachievements.org/oauth/token',
                            'scopes' => $scopes,
                        ],
                    ],
                ],
            ],
        ],
    ],

    /**
     * Do NOT enable this. Examples are read from database rows.
     */
    'examples' => false,

    'filesystem_disk' => 'local',
];
