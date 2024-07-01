<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'discord' => [
        'client_id' => env('DISCORD_KEY'),
        'client_secret' => env('DISCORD_SECRET'),
        'invite_id' => env('DISCORD_INVITE_ID'),
        'webhook' => [
            // public
            'achievements' => env('DISCORD_WEBHOOK_ACHIEVEMENTS'),
            'news' => env('DISCORD_WEBHOOK_NEWS'),
            'users' => env('DISCORD_WEBHOOK_USERS'),
            // moderation
            'forums' => env('DISCORD_WEBHOOK_MOD_FORUMS'),
            'sentry' => env('DISCORD_WEBHOOK_MOD_SENTRY'),
        ],
        'inbox_webhook' => [
            'DevCompliance' => [
                'url' => env('DISCORD_WEBHOOK_DEVCOMPLIANCE'),
                'is_forum' => true,
                'mention_role' => env('DISCORD_ROLE_DEVCOMPLIANCE'),
                'unwelcome_concept_url' => env('DISCORD_WEBHOOK_DEVCOMPLIANCE_UNWELCOME_CONCEPT'),
            ],
            'DevQuest' => [
                'url' => env('DISCORD_WEBHOOK_DEVQUEST'),
            ],
            'QATeam' => [
                'url' => env('DISCORD_WEBHOOK_QATEAM'),
                'is_forum' => true,
                'mention_role' => env('DISCORD_ROLE_QATEAM'),
            ],
            'QualityQuest' => [
                'url' => env('DISCORD_WEBHOOK_QUALITYQUEST'),
            ],
            'RACheats' => [
                'url' => env('DISCORD_WEBHOOK_RACHEATS'),
                'is_forum' => true,
                'mention_role' => env('DISCORD_ROLE_INVESTIGATOR'),
            ],
            'RAdmin' => [
                'url' => env('DISCORD_WEBHOOK_MOD'),
                'is_forum' => true,
                'mention_role' => [
                    env('DISCORD_ROLE_ADMIN'),
                    env('DISCORD_ROLE_MODERATOR'),
                ],
                'manual_unlock_url' => env('DISCORD_WEBHOOK_MOD_MANUAL_UNLOCK'),
                'verify_url' => env('DISCORD_WEBHOOK_MOD_VERIFY'),
            ],
            'RAEvents' => [
                'url' => env('DISCORD_WEBHOOK_RAEVENTS'),
                'is_forum' => true,
                'mention_role' => env('DISCORD_ROLE_EVENTS'),
            ],
            'RANews' => [
                'url' => env('DISCORD_WEBHOOK_RANEWS'),
            ],
        ],
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'client_token' => env('FACEBOOK_CLIENT_TOKEN'),
        'redirect' => 'http://your-callback-url',
        'channel' => env('FACEBOOK_CHANNEL'),
    ],

    'github' => [
        'organisation' => env('GITHUB_ORG'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_KEY'),
        'client_secret' => env('GOOGLE_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'recaptcha_key' => env('GOOGLE_RECAPTCHA_KEY'),
        'recaptcha_secret' => env('GOOGLE_RECAPTCHA_SECRET'),
    ],

    'patreon' => [
        'client_id' => env('PATREON_KEY'),
        'client_secret' => env('PATREON_SECRET'),
        'redirect' => env('PATREON_REDIRECT_URI'),
        'user_id' => env('PATREON_USER_ID'),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'reddit' => [
        'channel' => env('REDDIT_CHANNEL'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'twitch' => [
        'client_id' => env('TWITCH_KEY'),
        'client_secret' => env('TWITCH_SECRET'),
        'redirect' => env('TWITCH_REDIRECT_URI'),
        'channel' => env('TWITCH_CHANNEL'),
        'streamer_key' => env('TWITCH_STREAMER_KEY'),
    ],

    'threads' => [
        'channel' => env('THREADS_CHANNEL'),
    ],

    'twitter' => [
        'channel' => env('TWITTER_CHANNEL'),
        'widget_id' => env('TWITTER_WIDGET_ID'),
    ],

];
