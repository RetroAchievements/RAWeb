<?php

declare(strict_types=1);

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

            // qa

            'qateam_inbox' => env('DISCORD_WEBHOOK_QA_QATEAM_MESSAGES'),

            // moderation
            'forums' => env('DISCORD_WEBHOOK_MOD_FORUMS'),
            'sentry' => env('DISCORD_WEBHOOK_MOD_SENTRY'),
            'radmin_inbox' => env('DISCORD_WEBHOOK_MOD_RADMIN_MESSAGES'),
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

    'twitter' => [
        'channel' => env('TWITTER_CHANNEL'),
        'widget_id' => env('TWITTER_WIDGET_ID'),
    ],

];
